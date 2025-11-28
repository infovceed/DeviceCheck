<?php

namespace App\Exports;

use App\Models\Check;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class ChecksExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading, ShouldQueue
{
    use Exportable;

    /** @var array */
    protected array $filters;

    /** @var \App\Models\User */
    protected User $user;

    public function __construct(array $filters, User $user)
    {
        $this->filters = $filters;
        $this->user = $user;
    }

    public function query()
    {
        // Selección cruda con transformaciones solicitadas directamente en SQL
        $query = Check::query()->select([
            'id',
            'department',
            'municipality',
            'position_name',
            'tel',
            'device_key',
            DB::raw('DATE(created_at) as created_date'),
            DB::raw('(distance * 1000) as distance_m'),
            // Incluir columnas originales para que el accessor time_difference_minutes funcione
            'report_time',
            'time',
            DB::raw('TIME(report_time) as report_time_only'),
            DB::raw('TIME(time) as arrival_time_only'),
            DB::raw("ROUND(ABS(TIMESTAMPDIFF(SECOND, report_time, time)) / 60, 2) as time_difference_minutes"),
            'code',
            DB::raw("CASE WHEN type='checkin' THEN 'entrada' WHEN type='checkout' THEN 'salida' ELSE type END as type_label"),
        ]);

        if ($this->user->hasAccess('platform.systems.devices.show-department')) {
            $departmentId = $this->user->department_id;
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
        }

        $filters = $this->filters ?? [];
        Check::applyAllowedFilters($query, $filters);
        $query->orderBy('id');
        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            __('Department'),
            __('Municipality'),
            __('Position'),
            __('Mobile'),
            __('Key'),
            __('Report date'),
            __('Distance').' (m)',
            __('Report time'),
            __('Arrival time'),
            __('Time difference (minutes)'),
            __('Position code'),
            __('Type'),
        ];
    }

    public function map($check): array
    {
        return [
            $check->id,
            $check->department,
            $check->municipality,
            $check->position_name,
            $check->tel,
            $check->device_key,
            $check->created_date,
            $check->distance_m,
            $check->report_time_only,
            $check->arrival_time_only,
            $check->time_difference_minutes,
            $check->code,
            $check->type_label,
        ];
    }
    
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F81BD'], // Color de fondo del header
                ],
            ],
        ];
    }

    public function chunkSize(): int
    {
        // Tamaño de chunk equilibrado para memoria vs rendimiento
        return 1000;
    }
}
