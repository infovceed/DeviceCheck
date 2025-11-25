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
        $sql = "CREATE OR REPLACE VIEW checks AS
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
                devices.report_time
                FROM device_checks
                INNER JOIN devices ON devices.id=device_checks.device_id
                INNER JOIN divipoles ON divipoles.id=devices.divipole_id
                INNER JOIN departments ON departments.id=divipoles.department_id
                INNER JOIN municipalities ON municipalities.id=divipoles.municipality_id;
        ";
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
