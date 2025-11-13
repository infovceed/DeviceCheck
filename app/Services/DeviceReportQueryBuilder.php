<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DeviceReportQueryBuilder
{
    public static function build(array $filter, $userId, $hasShowAllAccess)
    {
        return DB::table('devices_report')
            ->select([
                DB::raw("
                    ID,
                    `Codigo CAD/PD`,
                    Corporacion,
                    Departamento,
                    Municipio,
                    Zona,
                    Puesto,
                    Mesa,
                    Estado,
                    `Kit No`
                "),
            ])
            ->when(! $hasShowAllAccess, fn($q) => $q->where('updated_by', $userId))
            ->when(isset($filter['department.name']), function ($query) use ($filter) {
                $query->where('Departamento', $filter['department.name']);
            })
            ->when(isset($filter['municipality.name']), function ($query) use ($filter) {
                $query->where('Municipio', $filter['municipality.name']);
            })
            ->when(isset($filter['divipole.zone_code']), function ($query) use ($filter) {
                $query->where('Zona', $filter['divipole.zone_code']);
            })
            ->when(isset($filter['divipole.position_code']), function ($query) use ($filter) {
                $query->where('Puesto', $filter['divipole.position_code']);
            })
            ->when(isset($filter['divipole.polling_station']), function ($query) use ($filter) {
                $query->where('Mesa', $filter['divipole.polling_station']);
            })
            ->when(isset($filter['divipole.kit_number']), function ($query) use ($filter) {
                $query->where('Kit No', (int)$filter['divipole.kit_number']);
            })
            ->when(isset($filter['status']), function ($query) use ($filter) {
                $query->where('status', $filter['status']);
            })
            ->orderBy('Devices.id', 'asc')
            ->toRawSql();
    }
}
