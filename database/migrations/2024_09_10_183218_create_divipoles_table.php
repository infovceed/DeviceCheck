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
        Schema::create('divipoles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->string('code')->maxLength(10)->comment('Código de la Divipol QR');
            $table->string('position_name')->comment('Nombre del puesto');
            $table->string('position_address')->comment('Dirección del puesto');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->boolean('is_backup')->default(false)->comment('Indica si es un puesto de respaldo');
            $table->timestamps();
        });
        Schema::table('divipoles', function (Blueprint $table) {
            $table->index('code', 'divipoles_code_index');
            $table->index('department_id', 'divipoles_department_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('divipoles');
        Schema::enableForeignKeyConstraints();
    }
};
