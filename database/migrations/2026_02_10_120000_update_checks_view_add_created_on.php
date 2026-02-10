<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Exponer created_on (DATE indexable) en el view para que los filtros por fecha
        // no usen DATE(created_at), que provoca full scan en tablas grandes.
        DB::unprepared(<<<SQL
            CREATE OR REPLACE VIEW checks AS
            SELECT
                dc.id AS id,
                dc.device_id,
                dc.distance,
                dc.type,
                dc.time,
                dc.created_at,
                dc.created_on AS created_on,
                dv.department_id,
                dpt.name AS department,
                dv.municipality_id,
                m.name AS municipality,
                dv.position_name,
                dv.code,
                d.tel,
                d.device_key,
                CASE
                    WHEN dc.type = 'checkin' THEN d.report_time
                    ELSE d.report_time_departure
                END AS report_time
            FROM device_checks dc
            INNER JOIN devices d ON d.id = dc.device_id
            INNER JOIN divipoles dv ON dv.id = d.divipole_id
            INNER JOIN departments dpt ON dpt.id = dv.department_id
            INNER JOIN municipalities m ON m.id = dv.municipality_id
        SQL);
    }

    public function down(): void
    {
        // Revertir al view anterior (sin created_on)
        DB::unprepared(<<<SQL
            CREATE OR REPLACE VIEW checks AS
            SELECT
                device_checks.id AS id,
                device_checks.device_id,
                device_checks.distance,
                device_checks.type,
                device_checks.time,
                device_checks.created_at,
                divipoles.department_id,
                departments.name AS department,
                divipoles.municipality_id,
                municipalities.name AS municipality,
                divipoles.position_name,
                divipoles.code,
                devices.tel,
                devices.device_key,
                CASE
                    WHEN device_checks.type = 'checkin' THEN devices.report_time
                    ELSE devices.report_time_departure
                END AS report_time
            FROM device_checks
            INNER JOIN devices ON devices.id = device_checks.device_id
            INNER JOIN divipoles ON divipoles.id = devices.divipole_id
            INNER JOIN departments ON departments.id = divipoles.department_id
            INNER JOIN municipalities ON municipalities.id = divipoles.municipality_id
        SQL);
    }
};
