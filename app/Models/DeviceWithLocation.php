<?php

namespace App\Models;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected $fillable = [
        'id', 'tel', 'device_key', 'report_time', 'report_time_departure',
        'position_name', 'code', 'department', 'municipality'
    ];

    /**
     * Build query for devices missing reports by department, type and dates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $deptNames
     * @param string $type
     * @param array $dates Array of Y-m-d strings
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMissingFor($query, array $deptNames, string $type, array $dates)
    {
        return $query
            ->select([
                DB::raw('devices_with_locations.id as id'),
                'tel',
                'device_key',
                'report_time',
                'report_time_departure',
                'position_name',
                'code',
                'department',
                'municipality',
            ])
            ->whereIn('department', $deptNames)
            ->leftJoin('device_checks as c', function($join) use ($type, $dates) {
                $join->on('c.device_id', '=', 'devices_with_locations.id')
                     ->where('c.type', '=', $type)
                     ->whereIn('c.created_on', $dates);
            })
            ->whereNull('c.id')
            ->orderBy('department')
            ->orderBy('municipality')
            ->orderBy('position_name');
    }
}
