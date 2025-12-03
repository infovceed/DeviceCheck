<?php

namespace App\Orchid\Screens\Check;

use App\Models\Check;
use App\Models\Device;
use Orchid\Screen\Screen;
use Illuminate\Http\Request;
use App\Traits\ComponentsTrait;
use App\Models\DeviceDailyCheck;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use Illuminate\Support\Facades\DB;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\ModalToggle;
use App\Services\DeviceReportQueryBuilder;
use App\Orchid\Layouts\Check\CheckListLayout;
use App\Orchid\Layouts\Check\DepartmentSummaryLayout;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ChecksExport;
use App\Jobs\NotifyUserOfCompletedExport;
use App\Notifications\DashboardNotification;

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
        $checks = Check::query()
            ->filters()
            ->when(auth()->user()->hasAccess('platform.systems.devices.show-department'), function ($query) {
                $departmentId = auth()->user()->department_id;
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }
            })
            ->defaultSort('id', 'asc')
            ->paginate();
        $data['checks'] = $checks;
     
        $filters = request()->query('filter', []);
        // show summary when department and type filters are present (date filter optional)
        $showSummary = !empty($filters['department']) && isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);
        if ($showSummary) {
            if (!empty($filters['created_at']) && empty($filters['check_day'])) {
                $inputFilters = request()->input('filter', $filters);
                $inputFilters['check_day'] = $filters['created_at'];
                request()->merge(['filter' => $inputFilters]);
            }

            $query = DeviceDailyCheck::query()->filters();

            // Selección: contar dispositivos distintos (no filas) y reported por tipo
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
            $rows = $query->get();

            $summary = $rows->map(function ($row) {
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
            $data['departmentSummary'] = $summary;
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
        $layout[] = CheckListLayout::class;
        $filters = request()->query('filter', []);
        $showSummary = !empty($filters['department']) && isset($filters['type']) && !empty($filters['type']) && isset($filters['created_at']) && !empty($filters['created_at']);

        if ($showSummary) {
            $layout[] = DepartmentSummaryLayout::class;
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
    
}
