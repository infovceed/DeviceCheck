<?php

namespace App\Orchid\Screens\Device;

use App\Models\User;
use Orchid\Screen\TD;
use App\Models\Device;
use App\Models\Divipole;
use Orchid\Screen\Screen;
use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Http\Request;
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
use Orchid\Screen\Actions\ModalToggle;
use App\Services\DeviceReportQueryBuilder;
use App\Orchid\Layouts\Device\Modal\EditDeviceModalLayout;

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
        $devices = Device::query()
            ->filters()
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
            'devices' => $devices,
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
            Layout::table('devices', [
                TD::make('id', 'ID')
                    ->sort()
                    ->align(TD::ALIGN_CENTER)
                    ->render(fn(Device $device) =>
                        ModalToggle::make((string)$device->id)
                            ->modal('editDeviceModal')
                            ->method('update', ['device' => $device->id])
                            ->asyncParameters(['device' => $device->id])
                    ),
                TD::make('department', __('Department'))
                    ->sort()
                    ->filter(
                        Relation::make('department')
                            ->fromModel(Department::class, 'name','name')
                            ->multiple()
                    )
                    ->render(fn(Device $device) => $device->divipole->department->name ?? ''),
                TD::make('municipality', __('Municipality'))
                    ->sort()
                    ->filter(
                        Relation::make('municipality')
                            ->fromModel(Municipality::class, 'name','name')
                            ->multiple()
                    )
                    ->render(fn(Device $device) => $device->divipole->municipality->name ?? ''),
                TD::make('position_name', __('Position'))
                    ->sort()
                    ->filter(
                        Relation::make('position_name')
                            ->fromModel(Divipole::class, 'position_name','position_name')
                            ->multiple()
                    )->filterValue(function ($value) {
                            if (is_array($value)) {
                                return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $value));
                            }
                    })
                    ->render(fn(Device $device) => $device->divipole->position_name ?? ''),
                TD::make('operative', __('Operative'))
                    ->sort()
                    ->filter(
                        Relation::make('operative')
                            ->fromModel(User::class, 'name')
                            ->multiple()
                    )
                    ->filterValue(function ($value) {
                        if (is_array($value)) {
                            $names = User::whereIn('id', $value)->pluck('name')->toArray();
                            return implode(', ', array_map(fn($v) => mb_strimwidth($v, 0, 10, '...'), $names));
                        }
                    })
                    ->render(fn(Device $device) => $device->user->name ?? $this->badge([
                        'text'  => __('No operative assigned'),
                        'color' => 'warning',
                    ])),
                TD::make('tel', __('Phone'))
                    ->sort()
                    ->filter(TD::FILTER_TEXT)
                    ->render(fn(Device $device) => $device->tel ?? ''),
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
                TD::make('report_time', __('Report time (Arrival)'))
                    ->render(fn(Device $device) => $device->report_time ?? __('Not Reported')),
                TD::make('report_time_departure', __('Report time (Departure)'))
                    ->render(fn(Device $device) => $device->report_time_departure ?? __('Not Reported')),
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
                    })->canSee(config('incidents.enabled') && auth()->user()->hasAccess('platform.systems.incidents.report')),
                TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Device $device) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list(array_filter([
                        config('incidents.enabled')
                            ? Link::make(__('Incidents'))
                                ->route('platform.systems.incidents', ['device' => $device->id])
                                ->icon('bs.pencil')
                                ->canSee(auth()->user()->hasAccess('platform.systems.incidents.report')&&config('incidents.enabled'))
                            : null,

                        ModalToggle::make(__('Edit'))
                            ->icon('bs.pencil')
                            ->modal('editDeviceModal')
                            ->method('update', ['device' => $device->id])
                            ->asyncParameters([
                                'device' => $device->id,
                            ])
                            ->canSee(auth()->user()->hasAccess('platform.systems.devices.edit')),
                    ])))
                ->canSee((
                        config('incidents.enabled') &&
                        auth()->user()->hasAccess('platform.systems.incidents.report')
                    ) ||
                    auth()->user()->hasAccess('platform.systems.devices.edit')
                ),
                ]),
            Layout::modal('editDeviceModal', [
                EditDeviceModalLayout::class
            ])->title(__('Edit device'))
              ->applyButton(__('Save'))
              ->closeButton(__('Cancel'))
              ->async('asyncGetDevice')
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
    public function asyncGetDevice(Device $device)
    {
        return [
            'payload' => [
                'tel'                   => $device->tel,
                'imei'                  => $device->imei,
                'device_key'            => $device->device_key,
                'sequential'            => $device->sequential,
                'user_id'               => $device->user_id,
                'latitude'              => $device->latitude,
                'longitude'             => $device->longitude,
                'report_time'           => $device->report_time,
                'report_time_departure' => $device->report_time_departure,
            ],
        ];
    }

    public function update(Device $device, Request $request): void
    {
        try {
            $data = $request->get('payload', []);

            if (!is_array($data)) {
                $data = [];
            }

            $fillable = [
                'tel',
                'imei',
                'device_key',
                'sequential',
                'user_id',
                'latitude',
                'longitude',
                'report_time',
                'report_time_departure',
            ];

            foreach ($fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $device->{$field} = $data[$field] !== '' ? $data[$field] : null;
                }
            }

            $device->updated_by = auth()->id();
            $device->save();

            Toast::info(__('Device edited successfully.'));
        } catch (\Exception $e) {
            Log::error($e);
            Alert::error(__('There was an error updating the Device report. Please try again.'));
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
                'status'  => $status,
                'output'  => $output,
                'script'  => $script,
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
