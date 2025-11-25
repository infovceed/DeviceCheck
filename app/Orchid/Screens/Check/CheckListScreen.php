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
        $showSummary = !empty($filters['department']) && !empty($filters['type']);
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

            // Filtro por departamento
            /* if (!empty($filters['department'])) {
                $deptFilter = (array) $filters['department'];
                $query->whereIn('department', $deptFilter);
            }
            // filtro por fechas separadas por comas
            if (!empty($filters['created_at'])) {
                $dateFilter = (array) $filters['created_at'];
                $query->whereIn(DB::raw('DATE(check_day)'), $dateFilter);
            } */

            $raw=$query->toRawSql();
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
                ->canSee(auth()->user()->hasAccess('platform.systems.devices.export'))
                ->download()
                ->method('export',[
                    'filter' => request()->query('filter', []),
                ]),
        ];
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
        $showSummary = !empty($filters['department']) && !empty($filters['type']&& !empty($filters['created_at']));

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

    public function export(Request $request, array $filter = [] )
    {
        $exportDir = storage_path('app/exports/Device_report');
        if (! file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $userId  = auth()->id();
        $date    = date('YmdHis');
        $sqlPath = "{$exportDir}/{$userId}.sql";
        $csvPath = "{$exportDir}/{$userId}-{$date}.csv";

        $hasShowAllAccess = auth()->user()->hasAccess('platform.systems.Devices.show-all');

        // 1) Genero el SQL de consulta
        $sql = DeviceReportQueryBuilder::build($filter, $userId, $hasShowAllAccess);
        Log::info('SQL generado para exportación', ['sql' => $sql]);
        file_put_contents($sqlPath, $sql);

        // 2) Ejecuta el script
        $script  = "{$exportDir}/exportExcel.sh";
        exec("sh {$script} {$sqlPath} {$csvPath}", $output, $status);

        if ($status !== 0 || ! file_exists($csvPath)) {
            Log::error('Error ejecutando exportExcel.sh', [
                'status' => $status,
                'output' => $output,
                'script' => $script,
                'sqlPath' => $sqlPath,
                'csvPath' => $csvPath,
            ]);
            Toast::error(__('There was an error generating the CSV file. Please try again.'));
            return;
        }

        // 3) Devuelve la descarga y elimina el archivo
        return response()
            ->download($csvPath, "Device_report.csv", [
                'Content-Type' => 'text/csv'
            ])
            ->deleteFileAfterSend(true);
    }
}
