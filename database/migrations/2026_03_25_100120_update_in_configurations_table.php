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
        Schema::table('configurations', function (Blueprint $table) {
            $table->unsignedBigInteger('current_work_shift_id')->nullable()->after('devices_file');
            $table->foreign('current_work_shift_id')->references('id')->on('work_shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropForeign(['current_work_shift_id']);
            $table->dropColumn('current_work_shift_id');
        });
    }
};
