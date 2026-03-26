<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
            INNER JOIN configurations c ON c.id = 1
            WHERE d.work_shift_id = c.current_work_shift_id
        SQL);
    }

    public function down(): void
    {
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
};
