<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class DeviceWithLocation extends Model
{
    use Filterable,AsSource;
    protected $table = 'devices_with_locations';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $casts = [
        'report_time'           => 'datetime:H:i:s',
        'report_time_departure' => 'datetime:H:i:s',
    ];

    /**
     * Build query for devices missing reports by department, type and dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $deptNames
     * @param string $type
     * @param array $dates Array of Y-m-d strings
     * @param array $filters Optional filters (report_time, report_time_departure)
     */
    public function scopeMissingFor($query, array $deptNames, string $type, array $dates, array $filters = [])
    {
        return $query
            ->select([
                DB::raw('devices_with_locations.id as id'),
                'tel',
                'device_key',
                'report_time AS report_time_arrival',
                'report_time_departure',
                'position_name',
                'code',
                'department',
                'municipality',
            ])
            ->whereIn('department', $deptNames)
            ->whereNotExists(function ($sub) use ($type, $dates) {
                $sub->select(DB::raw(1))
                    ->from('device_checks as c')
                    ->whereColumn('c.device_id', 'devices_with_locations.id')
                    ->where('c.type', '=', $type)
                    ->whereIn('c.created_on', $dates);
            })
            ->when($type=='checkin'&& isset($filters['report_time']), function ($query) use ($filters) {
                $query->where('report_time', $filters['report_time']);
            })
            ->when($type=='checkout' && isset($filters['report_time']), function ($query) use ($filters) {
                $query->where('report_time_departure', $filters['report_time']);
            })
            ->when(isset($filters['municipality']), function ($query) use ($filters) {
                $query->whereIn('municipality', $filters['municipality']);
            })
            ->orderBy('department')
            ->orderBy('municipality')
            ->orderBy('position_name');
    }
}
