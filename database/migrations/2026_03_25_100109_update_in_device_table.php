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
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedBigInteger('work_shift_id')->nullable()->after('status');
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->onDelete('set null');
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->index(['imei', 'work_shift_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign(['work_shift_id']);
            $table->dropColumn('work_shift_id');
            $table->dropIndex(['imei', 'work_shift_id']);
        });
    }
};
