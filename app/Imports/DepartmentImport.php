<?php

namespace App\Imports;

use App\Models\Department;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DepartmentImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts,ShouldQueue
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
            if (!array_key_exists('id', $row) || !array_key_exists('cod_depto', $row) || !array_key_exists('departamento', $row)) {
                Log::info('Invalid row: ' . json_encode($row));
                return null;
            }
    
            return new Department([
                'id' => $row['id'],
                'code' => $row['cod_depto'],
                'name' => $row['departamento'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating department: ' . $e->getMessage());
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
