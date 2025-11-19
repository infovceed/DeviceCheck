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
        $sql = "CREATE TRIGGER trigger_distance
                BEFORE INSERT ON device_checks
                FOR EACH ROW
                BEGIN
                DECLARE ref_lat VARCHAR(255);
                DECLARE ref_lon VARCHAR(255);

                -- Obtener latitude y longitude del device en formato varchar
                SELECT latitude, longitude INTO ref_lat, ref_lon
                FROM devices
                WHERE id = NEW.device_id;

                -- Calcular distancia reemplazando ',' por '.' y casteando a DECIMAL 
                SET NEW.distance = getDistance(
                    CAST(REPLACE(NEW.latitude, ',', '.') AS DECIMAL(10,7)),
                    CAST(REPLACE(NEW.longitude, ',', '.') AS DECIMAL(10,7)),
                    CAST(REPLACE(ref_lat, ',', '.') AS DECIMAL(10,7)),
                    CAST(REPLACE(ref_lon, ',', '.') AS DECIMAL(10,7))
                );
                END;
        ";
        DB::unprepared($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sql = "DROP TRIGGER IF EXISTS trigger_distance;";
        DB::unprepared($sql);
    }
};
