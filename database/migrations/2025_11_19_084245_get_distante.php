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
        $sql = "CREATE FUNCTION getDistance(
                lat1 DOUBLE, lon1 DOUBLE,
                lat2 DOUBLE, lon2 DOUBLE
                ) RETURNS DOUBLE
                DETERMINISTIC
                NO SQL
                BEGIN
                DECLARE R INT DEFAULT 6371; -- radio Tierra en km
                DECLARE dLat DOUBLE;
                DECLARE dLon DOUBLE;
                DECLARE a DOUBLE;
                DECLARE c DOUBLE;

                SET dLat = RADIANS(lat2 - lat1);
                SET dLon = RADIANS(lon2 - lon1);

                SET a = SIN(dLat / 2) * SIN(dLat / 2) +
                        COS(RADIANS(lat1)) * COS(RADIANS(lat2)) *
                        SIN(dLon / 2) * SIN(dLon / 2);
                SET c = 2 * ATAN2(SQRT(a), SQRT(1 - a));

                RETURN R * c;
                END;
        ";
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sql = "DROP FUNCTION IF EXISTS getDistance;";
        DB::unprepared($sql);
    }
};
