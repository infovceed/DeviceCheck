<?php

namespace App\Models;

use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory,AsSource, Filterable;

    protected $fillable = [
        'id',
        'code',
        'name',
    ];
    //sort
    protected $allowedSorts = [
        'id',
        'code',
        'name',
        'created_at',
    ];
    //filters
    protected $allowedFilters = [
        'id'         => Where::class,
        'code'       => Like::class,
        'name'       => Like::class,
        'created_at' => Where::class,
    ];

    public function scopeOrderByName(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    public function devices()
    {
        return $this->hasManyThrough(
            Device::class,
            Divipole::class,
            'department_id',
            'divipole_id'
        );
    }
    /**
     * Get chart data (labels, total, reported) optionally filtered by department and cached.
     *
     * @param mixed $departmentID 'all' or integer id
     * @param float $minutes cache duration
     * @return array [labels, totals, reporteds]
     */
    public static function getChartData(string $date,array $departmentID = [],array $municipality = [],array $position = [], float $minutes = 1): array
    {
         $query = DB::table('departments')
                    ->leftJoin('divipoles', 'departments.id', '=', 'divipoles.department_id')
                    ->leftJoin('municipalities', 'divipoles.municipality_id', '=', 'municipalities.id')
                    ->leftJoin('devices', 'divipoles.id', '=', 'devices.divipole_id')
                    ->leftJoin('device_checks', function ($join) use ($date) {
                        $join->on('device_checks.device_id', '=', 'devices.id')
                             ->whereDate('device_checks.created_at', '=', $date);
                    });
        if ($departmentID !== []) {
            $query->whereIn('departments.id', $departmentID);
        }
        if ($municipality !== []) {
            $query->whereIn('municipalities.name', $municipality);
        }
        if ($position !== []) {
            $query->whereIn('divipoles.position_name', $position);
        }

        $rows = $query
            ->select(
                'departments.name as name',
                DB::raw('COUNT(DISTINCT devices.id) as total_devices'),
                DB::raw("COUNT(DISTINCT CASE WHEN device_checks.type = 'checkin' THEN devices.id END) as reported_checkin"),
                DB::raw("COUNT(DISTINCT CASE WHEN device_checks.type = 'checkout' THEN devices.id END) as reported_checkout")
            )
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('departments.name')
            ->get();
        return [
            $rows->pluck('name')->toArray(),
            $rows->pluck('total_devices')->toArray(),
            $rows->pluck('reported_checkin')->toArray(),
            $rows->pluck('reported_checkout')->toArray(),
        ];
    }

    /**
     * Get chart data grouped by municipality for a specific department.
     *
     * @param string $date
     * @param int    $departmentId
     * @param array  $municipality Optional filter of municipality names
     * @param array  $position     Optional filter of position names
     * @return array [labels, totals, reported_checkin, reported_checkout]
     */
    public static function getMunicipalityChartData(string $date, int $departmentId, array $municipality = [], array $position = []): array
    {
        $query = DB::table('municipalities')
            ->join('divipoles', 'municipalities.id', '=', 'divipoles.municipality_id')
            ->join('departments', 'departments.id', '=', 'divipoles.department_id')
            ->leftJoin('devices', 'divipoles.id', '=', 'devices.divipole_id')
            ->leftJoin('device_checks', function ($join) use ($date) {
                $join->on('device_checks.device_id', '=', 'devices.id')
                     ->whereDate('device_checks.created_at', '=', $date);
            })
            ->where('departments.id', $departmentId);

        if ($municipality !== []) {
            $query->whereIn('municipalities.name', $municipality);
        }

        if ($position !== []) {
            $query->whereIn('divipoles.position_name', $position);
        }

        $rows = $query
            ->select(
                'municipalities.name as name',
                DB::raw('COUNT(DISTINCT devices.id) as total_devices'),
                DB::raw("COUNT(DISTINCT CASE WHEN device_checks.type = 'checkin' THEN devices.id END) as reported_checkin"),
                DB::raw("COUNT(DISTINCT CASE WHEN device_checks.type = 'checkout' THEN devices.id END) as reported_checkout")
            )
            ->groupBy('municipalities.id', 'municipalities.name')
            ->orderBy('municipalities.name')
            ->get();

        return [
            $rows->pluck('name')->toArray(),
            $rows->pluck('total_devices')->toArray(),
            $rows->pluck('reported_checkin')->toArray(),
            $rows->pluck('reported_checkout')->toArray(),
        ];
    }


}
