<?php

namespace App\Orchid\Screens\Device;

use Orchid\Screen\TD;
use App\Models\Device;
use Orchid\Screen\Screen;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Traits\ComponentsTrait;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Actions\Button;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Facades\Layout;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use App\Services\DeviceReportQueryBuilder;
use App\Orchid\Layouts\Device\DeviceFiltersLayout;
use App\Orchid\Layouts\Device\Modal\CreateDeviceModalLayout;

class DeviceListScreen extends Screen
{
    use ComponentsTrait;
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $Devices = Device::query()
            ->filters(DeviceFiltersLayout::class)
            ->when(!auth()->user()->hasAccess('platform.systems.devices.show-all'), function ($query) {
                $query->where('updated_by', auth()->user()->id);
            })
            ->when(auth()->user()->hasAccess('platform.systems.devices.show-department'), function ($query) {
                $departmentId = auth()->user()->department_id;
                if ($departmentId) {
                    $query->whereHas('divipole', function ($q) use ($departmentId) {
                        $q->where('department_id', $departmentId);
                    });
                }
            })
            ->defaultSort('id', 'asc')
            ->paginate();
        return [
            'devices' => $Devices,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Device');
    }

    //permisos
    public function permission(): array
    {
        return [
            'platform.systems.devices',
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
            DeviceFiltersLayout::class,
            Layout::table('devices', [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER),
                TD::make('department.name', __('Department'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->divipole->department->name ?? ''),
                TD::make('municipality.name', __('Municipality'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->divipole->municipality->name ?? ''),
                TD::make('position_name', __('Position'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->divipole->position_name ?? ''),
                TD::make('imei', __('IMEI'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->imei ?? ''),
                TD::make('device_key', __('Key'))
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->device_key ?? ''),
                TD::make('sequential', __('Consecutivo'))
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->sequential ?? ''),
                TD::make('latitude', __('Latitud'))
                    ->render(fn(Device $device) => $device->latitude ?? '(N/A)'),
                TD::make('longitude', __('Longitud'))
                    ->render(fn(Device $device) => $device->longitude ?? '(N/A)'),
                TD::make('report_time', __('Hora de Reporte'))
                    ->render(fn(Device $device) => $device->report_time ?? __('Not Reported')),
                TD::make('status_incidents', __('Incidents'))
                    ->sort()
                    ->filter(
                        Select::make('status_incidents')
                            ->options([
                                1 => __('Opened'),
                                2 => __('Closed'),
                            ])
                            ->empty(__('No Incidents'))
                            ->title(__('Incidents'))
                    )
                    ->filterValue(function ($value) {
                        if ($value === 1) {
                            return __('Opened');
                        } elseif ($value === 2) {
                            return __('Closed');
                        }
                        return __('No Incidents');
                    })
                    ->render(function (Device $Device) {
                        $value = $Device->status_incidents;
                        if ($value === 1) {
                            return $this->badge([
                                'text'  => __('Opened'),
                                'color' => 'danger',
                            ]);
                        } elseif ($value === 2) {
                            return $this->badge([
                                'text'  => __('Closed'),
                                'color' => 'success',
                            ]);
                        }
                        return $this->badge([
                            'text'  => __('No Incidents'),
                            'color' => 'info',
                        ]);
                    })->canSee(auth()->user()->hasAccess('platform.systems.incidents.report')),
                TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Device $Device) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Incidents'))
                            ->route('platform.systems.incidents', ['device' => $Device->id])
                            ->icon('bs.pencil')
                            ->canSee(auth()->user()->hasAccess('platform.systems.incidents.report')),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                            ->method('remove', [
                                'id' => $Device->id,
                            ])
                            ->canSee(auth()->user()->hasAccess('platform.systems.devices.delete')),
                    ]))
                ->canSee(
                    auth()->user()->hasAccess('platform.systems.incidents.report') ||
                    auth()->user()->hasAccess('platform.systems.devices.delete')
                    ),
                ]),
            Layout::modal('createDeviceModal', [
                CreateDeviceModalLayout::class
            ])->title(__('Register packaging'))
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
