<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::unprepared(<<<SQL
            CREATE OR REPLACE VIEW devices_with_locations AS
            SELECT
                devices.id AS id,
                devices.tel AS tel,
                devices.device_key AS device_key,
                devices.report_time AS report_time,
                devices.report_time_departure AS report_time_departure,
                divipoles.position_name AS position_name,
                divipoles.code AS code,
                departments.name AS department,
                municipalities.name AS municipality
            FROM devices
            INNER JOIN divipoles ON divipoles.id = devices.divipole_id
            INNER JOIN departments ON departments.id = divipoles.department_id
            INNER JOIN municipalities ON municipalities.id = divipoles.municipality_id
            INNER JOIN configurations c ON c.id = 1
            WHERE devices.work_shift_id = c.current_work_shift_id
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<SQL
            CREATE OR REPLACE VIEW devices_with_locations AS
            SELECT
                devices.id AS id,
                devices.tel AS tel,
                devices.device_key AS device_key,
                devices.report_time AS report_time,
                devices.report_time_departure AS report_time_departure,
                divipoles.position_name AS position_name,
                divipoles.code AS code,
                departments.name AS department,
                municipalities.name AS municipality
            FROM devices
            INNER JOIN divipoles ON divipoles.id = devices.divipole_id
            INNER JOIN departments ON departments.id = divipoles.department_id
            INNER JOIN municipalities ON municipalities.id = divipoles.municipality_id
        SQL);
    }
};
