<?php

namespace App\Models;

use App\Models\User;
use Orchid\Screen\AsSource;
use Orchid\Filters\Filterable;
use Orchid\Filters\Types\Where;
use App\Filters\Types\WherePositionIn;
use Illuminate\Database\Eloquent\Model;
use App\Filters\Types\WhereDepartmentIn;
use App\Filters\Types\WhereMunicipalityIn;
use App\Filters\Types\WhereDeviceDivipoleUserIn;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use HasFactory,Filterable,AsSource;

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
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function divipole()
    {
        return $this->belongsTo(Divipole::class);
    }

    public function incidents()
    {
        return $this->hasMany(Incident::class);
    }

    public function departments()
    {
        return $this->hasManyThrough(
            Department::class,
            Divipole::class,
            'department_id',
            'divipole_id'
        );
    }

    public function deviceChecks()
    {
        return $this->hasMany(DeviceCheck::class);
    }

    public static function totalReportedByDepartment(int $departmentId = null)
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

    public static function saveReport(array $deviceData)
    {
        $device = self::where('imei', $deviceData['imei'])
                        ->whereHas('divipole', fn($query) => $query->where('code', $deviceData['puesto']))
                        ->first();
        if (!$device) {
            $device = new self();
            $device->imei = $deviceData['imei'];
        }
        $device->latitude = $deviceData['lat'];
        $device->longitude = $deviceData['lon'];
        $device->report_time = now();
        $device->status = true;
        $device->save();
    }

}
