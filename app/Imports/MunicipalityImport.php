<?php

namespace App\Imports;


use App\Models\Municipality;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MunicipalityImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts,ShouldQueue
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
            if (!array_key_exists('id', $row) || !array_key_exists('cod_mpio', $row) || !array_key_exists('municipio', $row)) {
                Log::info('Invalid row: ' . json_encode($row));
                return null;
            }
    
            return new Municipality([
                'id'   => $row['id'],
                'code' => $row['cod_mpio'],
                'name' => $row['municipio'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating municipality: ' . $e->getMessage());
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
