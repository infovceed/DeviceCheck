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
        Schema::create('device_checks', function (Blueprint $table) {
            $table->id();
            $table->string('latitude')->maxLength(30)->nullable()->comment('Latitud del dispositivo');
            $table->string('longitude')->maxLength(30)->nullable()->comment('Longitud del dispositivo');
            $table->decimal('distance', 10, 6)->nullable()->comment('Distancia calculada');
            $table->string('type')->maxLength(10)->nullable()->comment('0 sin reporte, 1 entrada y 2 salida');
            $table->time('time')->nullable()->comment('Hora del registro');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_checks');
    }
};
