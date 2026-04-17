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
        Schema::create('filter_hours_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('filter_hours_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('filter_hours_id')->references('id')->on('filter_hours')->onDelete('cascade');
            $table->string('type')->maxLength(10)->nullable()->comment('checkin entrada y checkout salida');
            $table->string('position_name')->comment('Nombre del puesto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filter_hours_departments');
    }
};
