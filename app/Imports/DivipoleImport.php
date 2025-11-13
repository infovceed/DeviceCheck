<?php

namespace App\Imports;

use App\Models\Divipole;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DivipoleImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts,ShouldQueue
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
                !array_key_exists('municipio_id', $row) ||
                !array_key_exists('departamento_id', $row) ||
                !array_key_exists('codigo', $row) ||
                !array_key_exists('nombre_puesto', $row) ||
                !array_key_exists('direccion_puesto', $row)

                ) {
                Log::info('Invalid row: ' . json_encode($row));
                return null;
            }
            return new Divipole([
                'id'              => $row['id'],
                'municipality_id' => $row['municipio_id'],
                'department_id'   => $row['departamento_id'],
                'code'            => $row['codigo'],
                'position_name'   => $row['nombre_puesto'],
                'position_address'=> $row['direccion_puesto'],
                'created_by'      => 1,
                'updated_by'      => 1,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating divipole: ' . $e->getMessage());
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
