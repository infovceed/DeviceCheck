<?php

namespace App\Orchid\Screens\Check;

use App\Models\User;
use App\Models\Check;
use App\Models\Device;
use Orchid\Screen\Screen;
use Illuminate\Http\Request;
use App\Exports\ChecksExport;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Group;
use App\Models\DeviceDailyCheck;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use App\Models\DeviceWithLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Fields\Relation;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Orchid\Screen\Actions\ModalToggle;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Notifications\DashboardNotification;
use App\Orchid\Layouts\Check\CheckListLayout;
use App\Orchid\Layouts\Check\CheckFiltersLayout;
use App\Orchid\Layouts\Check\MissingDevicesLayout;
use App\Orchid\Layouts\Check\DepartmentSummaryLayout;

class CheckListScreen extends Screen
{
    use ComponentsTrait;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $perPage = request()->input('perPage', 15);
        $checks = Check::query()
            ->filters()
            ->when(auth()->user()->hasAccess('platform.systems.devices.show-department'), function ($query) {
                $departmentId = auth()->user()->department_id;
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->defaultSort('id', 'asc')
            ->paginate($perPage);
        $data['checks'] = $checks;
     
        $filters = request()->query('filter', []);
        // Summary of departments
        $showSummary = !empty($filters['department']) && isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);
        if ($showSummary) {
            if (!empty($filters['created_at']) && empty($filters['check_day'])) {
                $inputFilters = request()->input('filter', $filters);
                $inputFilters['check_day'] = $filters['created_at'];
                request()->merge(['filter' => $inputFilters]);
            }
            $query = DeviceDailyCheck::query()->filters();
            if (!empty($filters['type']) && $filters['type'] === 'checkin') {
                $query->select(
                    'department as name',
                    'municipality',
                    DB::raw('COUNT(DISTINCT device_id) as total'),
                    DB::raw('COUNT(DISTINCT CASE WHEN has_checkin > 0 THEN device_id END) as reported')
                );
            } else {
                $query->select(
                    'department as name',
                    'municipality',
                    DB::raw('COUNT(DISTINCT device_id) as total'),
                    DB::raw('COUNT(DISTINCT CASE WHEN has_checkout > 0 THEN device_id END) as reported')
                );
            }

            $query->groupBy('department', 'municipality')
                ->orderBy('department');
            $summaryPage = request()->input('summaryPage', 1);
            $paginated = $query->paginate(15, ['*'], 'summaryPage', $summaryPage);
            $paginated->appends(request()->except(['summaryPage']));
            $transformed = $this->buildDepartmentSummary($paginated->getCollection());
            $paginated->setCollection($transformed);
            $data['departmentSummary'] = $paginated;
        }

        //Table of missing devices
        $showMissing = !empty($filters['department']) && !empty($filters['type']) && !empty($filters['created_at']);
        if ($showMissing) {
            $dates = $filters['created_at'];
            if (is_string($dates)) {
                $dates = array_filter(array_map('trim', explode(',', $dates)));
            }
            $dates = array_unique((array)$dates);

            $type = is_array($filters['type']) ? reset($filters['type']) : $filters['type'];
            $deptNames = (array)$filters['department'];
            $missingPage = request()->input('missingPage', 1);
            $missing = DeviceWithLocation::missingFor($deptNames, $type, $dates)
                ->paginate(15, ['*'], 'missingPage', $missingPage);
            $missing->appends(request()->except(['missingPage']));
            $data['missingDevices'] = $missing;
            $data['missingType']    = $type;
            $data['missingDates']   = $dates;
        }

        
        return $data;
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Device Checks');
    }

    //permisos
    public function permission(): array
    {
        return [
            'platform.systems.device-check',
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Add'))
                ->modal('createDeviceModal')
                ->icon('plus')
                ->method('create')
                ->canSee(auth()->user()->hasAccess('platform.systems.devices.create')),
            Button::make(__('Export'))
                ->icon('download')
                ->canSee(auth()->user()->hasAccess('platform.systems.report-download'))
                ->method('export', [
                    'filter' => request()->query('filter', []),
                ]),
                //boton para recargar la pagina
                Link::make(__('Refresh'))
                ->icon('bs.arrow-clockwise')
                ->route('platform.systems.devices-check'),
        ];
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        if (! $user->hasAccess('platform.systems.report-download')) {
            Alert::error('You do not have permission to export.');
            return;
        }

        $filters = $request->input('filter', $request->query('filter', []));

        $user->notify(new DashboardNotification(
            __('Excel export started'),
            __('We are generating your Excel file. You will be notified when it is ready.'),
        ));

        $timestamp = now()->format('Ymd_His');
        $fileName  = "checks_{$timestamp}.xlsx";
        $disk      = 'public';
        $path      = "exports/{$fileName}";

        $ttl = (int) config('export.signed_url_ttl', 60 * 24);
        $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'exports.download',
            now()->addMinutes($ttl),
            ['path' => $path]
        );
        Excel::queue(new ChecksExport($filters, $user), $path, $disk)
            ->chain([
                new NotifyUserOfCompletedExport($user, $downloadUrl, $fileName),
            ]);

        Toast::info(__('Your export has started. We will notify you when it finishes.'));
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {

        $layout[]  = new CheckFiltersLayout();
        $layout[]  = (new CheckListLayout())->title(__('Reported Devices'));
        $filters   = request()->query('filter', []);
        $showSummary = !empty($filters['department']) && isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);

        if ($showSummary) {
            $layout[] = Layout::split([
                (new DepartmentSummaryLayout())->title(__('Department Summary')),
                (new MissingDevicesLayout())->title(__('Missing Devices')),
            ])->ratio('50/50');
        }
        return $layout;
    }


    public function create(Request $request): void
    {
        try {

            Toast::info(__('Device report was created successfully.'));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::error(__('There was an error creating the Device report. Please try again.'));
            return;
        }

    }
    public function remove(Request $request): void
    {
        $device = Device::find($request->input('id'));
        $device->updated_by=null;
        $device->status = 0;
        $device->save();
        Toast::info(__('Device report was removed'));
    }
    
    /**
     * Build department summary DTOs from query rows.
     *
     * @param \Illuminate\Support\Collection $rows
     * @return \Illuminate\Support\Collection
     */
    protected function buildDepartmentSummary(Collection $rows): Collection
    {
        return $rows->map(function ($row) {
            $pending = max(0, $row->total - $row->reported);
            $pctReported = $row->total > 0 ? round(($row->reported / $row->total) * 100, 2) : 0;
            $pctPending = 100 - $pctReported;
            return (object) [
                'id'           => $row->id,
                'department'   => $row->name,
                'municipality' => $row->municipality,
                'total'        => (int) $row->total,
                'reported'     => (int) $row->reported,
                'pending'      => (int) $pending,
                'pct_reported' => $pctReported,
                'pct_pending'  => $pctPending,
            ];
        });
    }
    
}
