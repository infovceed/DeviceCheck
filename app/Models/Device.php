<?php

namespace App\Models;

use App\Filters\Types\WhereDepartmentIn;
use App\Filters\Types\WhereDeviceDivipoleUserIn;
use App\Filters\Types\WhereMunicipalityIn;
use App\Filters\Types\WherePositionIn;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use Orchid\Screen\AsSource;

/**
 * @property-read Divipole|null $divipole
 */
class Device extends Model
{
    use HasFactory;
    use Filterable;
    use AsSource;

    protected $fillable = [
        'divipole_id',
        'is_backup',
        'tel',
        'imei',
        'device_key',
        'sequential',
        'latitude',
        'longitude',
        'report_time',
        'report_time_departure',
        'status',
        'work_shift_id',
        'status_incidents',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'department'    => WhereDepartmentIn::class,
        'municipality'  => WhereMunicipalityIn::class,
        'position_name' => WherePositionIn::class,
        'operative'     => WhereDeviceDivipoleUserIn::class,
        'tel'           => Where::class,
        'status'        => Where::class,
        'imei'          => Where::class,
        'device_key'    => Where::class,
        'sequential'    => Where::class,
        'updated_at'    => Where::class,
        'is_backup'     => Where::class,
    ];

    protected $allowedSorts = [
        'report_time',
        'report_time_departure',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function divipole(): BelongsTo
    {
        return $this->belongsTo(Divipole::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function departments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Department::class,
            Divipole::class,
            'department_id',
            'divipole_id'
        );
    }

    public function deviceChecks(): HasMany
    {
        return $this->hasMany(DeviceCheck::class);
    }

    public static function totalReportedByDepartment(?int $departmentId = null)
    {
        $query = Department::query()
            ->withCount(['devices as total' => function ($q) {
                $q->where('status', 1);
            }])
            ->when($departmentId, fn($query) => $query->where('id', $departmentId));
        return $query
            ->get(['name'])
            ->pluck('total', 'name');
    }

    public static function getIncidentsOpen()
    {
        return self::where('status_incidents', 1)
        ->join('divipoles', 'devices.divipole_id', '=', 'divipoles.id')
        ->join('departments', 'divipoles.department_id', '=', 'departments.id')
        ->join('municipalities', 'divipoles.municipality_id', '=', 'municipalities.id')
        ->get(['devices.id as device_id', 'departments.name as department_name',
                'municipalities.name as municipality_name',
               'divipoles.position_name as position_name',
               'devices.tel as tel']);
    }
    public static function findByImeiWithLocation(array $params): ?self
    {
        $currentWorkShiftId = Cache::remember('config.current_work_shift_id', 30, function () {
            return Configuration::query()
                                ->whereKey(1)
                                ->value('current_work_shift_id');
        });
        return self::query()
            ->select(['id', 'divipole_id'])
            ->with([
                'divipole:id,department_id,municipality_id,position_name',
                'divipole.department:id,name',
                'divipole.municipality:id,name',
            ])
            ->where('work_shift_id', $currentWorkShiftId)
            ->where('imei', $params['imei'])
            ->first();
    }
}
