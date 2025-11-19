<?php

namespace App\Orchid\Screens\Check;

use Carbon\Carbon;
use App\Models\Check;
use Orchid\Screen\TD;
use App\Models\Device;
use Orchid\Screen\Screen;
use App\Models\Department;
use Illuminate\Http\Request;
use function Termwind\render;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Fields\Relation;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Actions\ModalToggle;
use App\Services\DeviceReportQueryBuilder;

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
        return [
            'checks' => $checks,
        ];
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
        return [
            Layout::table('checks', [
                TD::make('id', 'ID')
                    ->align(TD::ALIGN_CENTER),
                TD::make('department', __('Department'))
                    ->filter(
                       Relation::make('department')
                           ->fromModel(Department::class, 'name','name')
                    ),
                TD::make('municipality', __('Municipality'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('position_name', __('Position'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('tel', __('Mobile'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('device_key', __('Key'))
                    ->filter(TD::FILTER_TEXT),
                TD::make('created_at', __('Report date'))
                    ->filter(
                        DateTimer::make('open')
                            ->format('Y-m-d')
                    )
                    ->render(function(Check $check) {
                        if (! $check->created_at) {
                            return null;
                        }
                        $formatted = Carbon::parse($check->created_at)
                            ->locale(app()->getLocale() ?: 'es')
                            ->isoFormat('dddd D [de] MMMM [de] YYYY');

                        $first = mb_substr($formatted, 0, 1, 'UTF-8');
                        $rest  = mb_substr($formatted, 1, null, 'UTF-8');

                        return mb_strtoupper($first, 'UTF-8') . $rest;
                    }),
                TD::make('distance', __('Distance').' (m)')
                    ->render(function(Check $check) {
                        $distance = $check->distance*1000;
                        return $this->badge([
                            'text'  => $distance,
                            'color' => $distance<500 ? 'info' : 'danger',
                        ]);
                    })->alignCenter(),
                TD::make('report_time', __('Report time')),
                TD::make('time', __('Arrival time')),
                TD::make('time_difference_minutes', __('Time difference (minutes)'))->alignCenter(),
                TD::make('code', __('Position code'))
                    ->filter(TD::FILTER_TEXT)
                    ->alignCenter(),
                TD::make('type', __('Type'))
                    ->filter(
                        Select::make()
                            ->options([
                                'checkin'  => __('Check-in'),
                                'checkout' => __('Check-out'),
                            ])
                            ->empty(__('All'))
                    )
                    ->filterValue(fn($value) => match ($value) {
                        'checkin' => __('Check-in'),
                        'checkout' => __('Check-out'),
                        default => $value,
                    })
                    ->render(function(Check $check) {
                        return $this->badge([
                            'text'  => $check->type == 'checkin' ? __('Check-in') : __('Check-out'),
                            'color' => $check->type == 'checkin' ? 'success' : 'info',
                        ]);
                    }),
                
            ]),
        ];
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
