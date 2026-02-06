<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('device_checks', 'created_on')) {
            DB::statement('ALTER TABLE device_checks ADD COLUMN created_on DATE GENERATED ALWAYS AS (DATE(created_at)) STORED');
        }
        Schema::table('device_checks', function (Blueprint $table) {
            // Índices para acelerar filtros por fechas exactas y combinados
            $table->index('created_on', 'device_checks_created_on_index');
            $table->index(['device_id', 'type', 'created_on'], 'device_checks_device_type_created_on_index');
        });
    }

    public function down(): void
    {
        Schema::table('device_checks', function (Blueprint $table) {
            $table->dropIndex('device_checks_created_on_index');
            $table->dropIndex('device_checks_device_type_created_on_index');
        });
        if (Schema::hasColumn('device_checks', 'created_on')) {
            DB::statement('ALTER TABLE device_checks DROP COLUMN created_on');
        }
    }
};
