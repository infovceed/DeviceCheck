<?php

namespace App\Orchid\Screens\Device;

use App\Jobs\DeviceBulkUpdateJob;
use App\Models\Device;
use App\Orchid\Layouts\Device\DeviceListLayout;
use App\Orchid\Layouts\Device\Modal\BulkUpdateByExcelModalLayout;
use App\Orchid\Layouts\Device\Modal\EditDeviceModalLayout;
use App\Services\DeviceReportQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DeviceListScreen extends Screen
{
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
                ->method('export', [
                    'filter' => request()->query('filter', []),
                ]),
            ModalToggle::make(__('Bulk Update by Excel'))
                ->icon('cloud-upload')
                ->modal('bulkUpdateByExcelModal')
                ->method('bulkUpdateFromExcel')
                ->canSee(auth()->user()->hasAccess('platform.systems.devices.edit')),
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
            DeviceListLayout::class,
            Layout::modal('editDeviceModal', [
                EditDeviceModalLayout::class
            ])->title(__('Edit device'))
              ->applyButton(__('Save'))
              ->closeButton(__('Cancel'))
              ->async('asyncGetDevice'),
            Layout::modal('bulkUpdateByExcelModal', [
                    BulkUpdateByExcelModalLayout::class,
            ])->title(__('Update tel/IMEI via Excel'))
                ->applyButton(__('Update'))
                ->closeButton(__('Cancel'))
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
                'divipole_id'           => $device->divipole_id,
                'tel'                   => $device->tel,
                'imei'                  => $device->imei,
                'device_key'            => $device->device_key,
                'sequential'            => $device->sequential,
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
                'divipole_id',
                'tel',
                'imei',
                'device_key',
                'sequential',
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
        $device->updated_by = null;
        $device->status = 0;
        $device->save();
        Toast::info(__('Device report was removed'));
    }

    public function export(Request $request, array $filter = [])
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

    public function bulkUpdateFromExcel(Request $request): void
    {
        try {
            $file = $request->file('payload.file');
            if (!$file) {
                Alert::error(__('You must attach a file.'));
                return;
            }

            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['xlsx','xls','csv'])) {
                Alert::error(__('Unsupported format. Use .xlsx, .xls or .csv'));
                return;
            }

            $dir = 'imports/devices-bulk';
            $name = 'bulk_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $stored = $file->storeAs($dir, $name);
            $storedPath = storage_path('app/' . $stored);

            DeviceBulkUpdateJob::dispatch($storedPath, auth()->user());
            Toast::info(__('File enqueued for processing. You will be notified when it finishes.'));
        } catch (\Throwable $e) {
            Log::error('bulkUpdateFromExcel enqueue error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Alert::error(__('An error occurred while enqueuing the file.'));
            return;
        }
    }
}
