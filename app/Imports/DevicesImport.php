<?php

namespace App\Imports;

use App\Models\Device;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DevicesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts,ShouldQueue
{
    use Importable;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            if (!array_key_exists('id', $row) ||
                !array_key_exists('divipole_id', $row) ||
                !array_key_exists('tel', $row) ||
                !array_key_exists('imei', $row) ||
                !array_key_exists('llave', $row) ||
                !array_key_exists('consecutivo', $row)||
                !array_key_exists('llegada', $row)||
                !array_key_exists('salida', $row)||
                !array_key_exists('latitud', $row)||
                !array_key_exists('longitud', $row)

              ) {
                Log::info('Invalid row: ' . json_encode($row));
                return null;
            }
            return new Device([
                'id'                    => $row['id'],
                'divipole_id'           => $row['divipole_id'],
                'tel'                   => $row['tel'],
                'imei'                  => $row['imei'],
                'device_key'            => $row['llave'],
                'sequential'            => $row['consecutivo'],
                'report_time'           => $row['llegada'],
                'report_time_departure' => $row['salida'],
                'latitude'              => $row['latitud'],
                'longitude'             => $row['longitud'],
                'status'                => 0,
                'created_by'            => 1,
                'updated_by'            => 1,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Device: ' . $e->getMessage());
            return null;
        }
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
