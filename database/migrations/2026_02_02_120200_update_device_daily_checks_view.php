<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS device_daily_checks');
        DB::statement(<<<SQL
            CREATE VIEW device_daily_checks AS
            SELECT
                d.id AS device_id,
                d.tel,
                dpt.name AS department,
                m.name AS municipality,
                cd.date_value AS check_day,
                LEAST(COUNT(CASE WHEN dc.type = 'checkin' THEN 1 END), 1) AS has_checkin,
                LEAST(COUNT(CASE WHEN dc.type = 'checkout' THEN 1 END), 1) AS has_checkout
            FROM devices d
            CROSS JOIN calendar_dates cd
            LEFT JOIN device_checks dc
                ON dc.device_id = d.id
               AND dc.created_on = cd.date_value
            LEFT JOIN divipoles dv ON dv.id = d.divipole_id
            LEFT JOIN departments dpt ON dpt.id = dv.department_id
            LEFT JOIN municipalities m ON m.id = dv.municipality_id
            GROUP BY d.id, d.tel, dpt.name, m.name, cd.date_value
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS device_daily_checks');
    }
};
