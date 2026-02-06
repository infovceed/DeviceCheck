<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    protected function indexExists(string $table, string $index): bool
    {
        $res = DB::select(
            "SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?",
            [$table, $index]
        );
        return ($res[0]->cnt ?? 0) > 0;
    }

    public function up(): void
    {
        if (!Schema::hasColumn('device_checks', 'created_on')) {
            DB::statement('ALTER TABLE device_checks ADD COLUMN created_on DATE GENERATED ALWAYS AS (DATE(created_at)) STORED');
        }
        if (!$this->indexExists('device_checks', 'device_checks_created_on_index')) {
            DB::statement('CREATE INDEX device_checks_created_on_index ON device_checks (created_on)');
        }
        if (!$this->indexExists('device_checks', 'device_checks_device_type_created_on_index')) {
            DB::statement('CREATE INDEX device_checks_device_type_created_on_index ON device_checks (device_id, type, created_on)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('device_checks', 'device_checks_created_on_index')) {
            DB::statement('DROP INDEX device_checks_created_on_index ON device_checks');
        }
        if ($this->indexExists('device_checks', 'device_checks_device_type_created_on_index')) {
            DB::statement('DROP INDEX device_checks_device_type_created_on_index ON device_checks');
        }
        if (Schema::hasColumn('device_checks', 'created_on')) {
            DB::statement('ALTER TABLE device_checks DROP COLUMN created_on');
        }
    }
};
