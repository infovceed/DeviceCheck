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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('divipole_id')->constrained()->onDelete('cascade');
            $table->string('tel')->maxLength(30)->nullable()->comment('Numero de telefono del dispositivo');
            $table->string('imei')->maxLength(30)->nullable()->comment('Numero de IMEI del dispositivo');
            $table->string('device_key')->maxLength(30)->nullable()->comment('1 para empaque, 2 para simulacro');
            $table->string('sequential')->maxLength(30)->nullable()->comment('Numero CONSECUTIVO del dispositivo');
            $table->time('report_time')->nullable()->comment('Hora de reporte de llegada del dispositivo');
            $table->time('report_time_departure')->nullable()->comment('Hora de reporte de salida del dispositivo');
            $table->string('latitude')->maxLength(30)->nullable()->comment('Latitud del dispositivo');
            $table->string('longitude')->maxLength(30)->nullable()->comment('Longitud del dispositivo');
            $table->boolean('is_backup')->default(0)->comment('1 si es dispositivo de respaldo, 0 si no lo es');
            $table->boolean('status')->default(false)->comment('verdadero para reporte exitoso, falso para falta de reporte');
            $table->smallInteger('status_incidents')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->index('divipole_id', 'Devices_divipole_id_index');
            $table->index('status', 'Devices_status_index');
            $table->index('status_incidents', 'Devices_status_incidents_index');
            $table->index('updated_by', 'Devices_updated_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('devices');
        Schema::enableForeignKeyConstraints();
    }
};
