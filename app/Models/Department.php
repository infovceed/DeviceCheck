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
    public static function getChartData(int|string $departmentID = 'all', float $minutes = 1): array
    {
        $cacheKey = "dashboard.chart.{$departmentID}";
        return Cache::remember(
            $cacheKey,
            now()->addMinutes($minutes),
            function () use ($departmentID) {
                $query = DB::table('departments')
                    ->leftJoin('divipoles', 'departments.id', '=', 'divipoles.department_id')
                    ->leftJoin('devices', 'divipoles.id', '=', 'devices.divipole_id')
                    ->leftJoin('users', 'devices.updated_by', '=', 'users.id')
                    ->when($departmentID !== 'all', function ($query) use ($departmentID) {
                        $query->where('departments.id', $departmentID);
                    });

                $rows = $query
                    ->select(
                        'departments.name as name',
                        DB::raw('COUNT(devices.id) as total'),
                        DB::raw('SUM(CASE WHEN devices.status = 1 THEN 1 ELSE 0 END) as reported'),
                    )
                    ->groupBy('departments.id', 'departments.name')
                    ->orderBy('departments.name')
                    ->get();

                return [
                    $rows->pluck('name')->toArray(),
                    $rows->pluck('total')->toArray(),
                    $rows->pluck('reported')->toArray(),
                ];
            }
        );
    }


}
