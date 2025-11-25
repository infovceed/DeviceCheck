<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sql = "CREATE VIEW device_daily_checks AS
                SELECT
                    d.id AS device_id,
                    d.tel,
                    dpt.name AS department,
                    m.name AS municipality,
                    cd.date_value AS check_day,
                    LEAST(COUNT(CASE WHEN dc.type = 'checkin' AND DATE(dc.created_at) = cd.date_value THEN 1 END), 1) AS has_checkin,
                    LEAST(COUNT(CASE WHEN dc.type = 'checkout' AND DATE(dc.created_at) = cd.date_value THEN 1 END), 1) AS has_checkout_count
                FROM devices d
                CROSS JOIN calendar_dates cd
                LEFT JOIN device_checks dc
                    ON dc.device_id = d.id
                LEFT JOIN divipoles dv ON dv.id = d.divipole_id
                LEFT JOIN departments dpt ON dpt.id = dv.department_id
                LEFT JOIN municipalities m ON m.id = dv.municipality_id
                GROUP BY d.id, d.tel, dpt.name, m.name, cd.date_value
                ;
        ";
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS device_daily_checks');
    }
};
