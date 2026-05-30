<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Cubre el join principal de la vista device_daily_checks:
            // WHERE d.work_shift_id = c.current_work_shift_id → JOIN dv ON dv.id = d.divipole_id
            $table->index(['work_shift_id', 'divipole_id'], 'devices_work_shift_divipole_index');
        });

        Schema::table('divipoles', function (Blueprint $table) {
            // Cubre los filtros del WHERE externo sobre la vista:
            // department, municipality, position_name
            $table->index(
                ['department_id', 'municipality_id', 'position_name'],
                'divipoles_dept_muni_position_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Soltar FK antes del índice porque MariaDB puede haberla asignado a él
            $table->dropForeign(['work_shift_id']);
            $table->dropIndex('devices_work_shift_divipole_index');
            // Recrear la FK — usará el índice (imei, work_shift_id) existente
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('set null');
        });

        Schema::table('divipoles', function (Blueprint $table) {
            $table->dropIndex('divipoles_dept_muni_position_index');
        });
    }
};
