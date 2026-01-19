<?php

namespace App\Imports;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use App\Jobs\NotifyUserOfImportError;
use Illuminate\Validation\Rule;

class DevicesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, WithValidation, SkipsOnFailure, ShouldQueue
{
    use Importable, SkipsFailures;

    private User $user;
    private array $seenTel = [];
    private array $seenImei = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        try {
            return new Device([
                'id'                    => $row['id'],
                'divipole_id'           => $row['divipole_id'],
                'tel'                   => isset($row['tel']) ? trim((string)$row['tel']) : null,
                'imei'                  => isset($row['imei']) ? trim((string)$row['imei']) : null,
                'device_key'            => $row['llave'],
                'sequential'            => $row['consecutivo'],
                'report_time'           => $row['llegada'],
                'report_time_departure' => $row['salida'],
                'latitude'              => $row['latitud'],
                'longitude'             => $row['longitud'],
                'is_backup'             => $row['esbackup'] ?? 0,
                'status'                => 0,
                'created_by'            => 1,
                'updated_by'            => 1,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating Device: ' . $e->getMessage());
            // Notificar el error inesperado al usuario (sin número de fila específico)
            dispatch(new NotifyUserOfImportError(
                $this->user,
                'Error en importación de dispositivos',
                'Se produjo un error procesando un registro: '.$e->getMessage()
            ));
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

    public function headingRow(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [
            'id'               => 'required|integer',
            'divipole_id'      => 'required|integer|exists:divipoles,id',
            'tel'              => [
                'required',
                Rule::unique('devices', 'tel'),
                function ($attribute, $value, $fail) {
                    $val = trim((string)$value);
                    if ($val === '') {
                        return; // 'required' ya se encarga
                    }
                    if (isset($this->seenTel[$val])) {
                        $fail("El valor del campo '{$attribute}' está repetido en el archivo.");
                    } else {
                        $this->seenTel[$val] = true;
                    }
                },
            ],
            'imei'             => [
                'required',
                Rule::unique('devices', 'imei'),
                function ($attribute, $value, $fail) {
                    $val = trim((string)$value);
                    if ($val === '') {
                        return; // 'required' ya se encarga
                    }
                    if (isset($this->seenImei[$val])) {
                        $fail("El valor del campo '{$attribute}' está repetido en el archivo.");
                    } else {
                        $this->seenImei[$val] = true;
                    }
                },
            ],
            'llave'            => 'required',
            'consecutivo'      => 'required',
            'llegada'          => 'required',
            'salida'           => 'required',
            'latitud'          => 'required',
            'longitud'         => 'required',
            'esbackup'         => 'nullable|in:0,1',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id.required'                  => 'El campo id es obligatorio.',
            'id.integer'                   => 'El campo id debe ser numérico.',
            'divipole_id.required'         => 'El campo divipole_id es obligatorio.',
            'divipole_id.exists'           => 'El divipole_id no existe.',
            'tel.required'                 => 'El campo tel es obligatorio.',
            'tel.unique'                   => 'El número de teléfono ya existe.',
            'imei.required'                => 'El campo imei es obligatorio.',
            'imei.unique'                  => 'El IMEI ya existe.',
            'llave.required'               => 'El campo llave es obligatorio.',
            'consecutivo.required'         => 'El campo consecutivo es obligatorio.',
            'llegada.required'             => 'El campo llegada es obligatorio.',
            'salida.required'              => 'El campo salida es obligatorio.',
            'latitud.required'             => 'El campo latitud es obligatorio.',
            'longitud.required'            => 'El campo longitud es obligatorio.',
            'esbackup.in'                  => 'El campo esbackup debe ser 0 o 1.',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $attribute = $failure->attribute();
            $row = $failure->row();
            $errors = implode('; ', $failure->errors());

            dispatch(new NotifyUserOfImportError(
                $this->user,
                'Error en importación de dispositivos',
                "Fila {$row}: Campo '{$attribute}' - {$errors}"
            ));
        }
    }
}
