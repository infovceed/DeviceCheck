<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('device_checks', function (Blueprint $table) {
            // Índice compuesto para filtros por tipo + fecha
            $table->index(['type', 'created_at'], 'device_checks_type_created_at_index');
            // Índice compuesto para búsquedas por dispositivo + tipo + fecha (missing devices)
            $table->index(['device_id', 'type', 'created_at'], 'device_checks_device_type_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('device_checks', function (Blueprint $table) {
            $table->dropIndex('device_checks_type_created_at_index');
            $table->dropIndex('device_checks_device_type_created_at_index');
        });
    }
};
