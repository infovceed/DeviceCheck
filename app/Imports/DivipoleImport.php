<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Divipole;
use Illuminate\Support\Facades\Log;
use App\Jobs\NotifyUserOfImportError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class DivipoleImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue, WithValidation, SkipsOnFailure
{
    use Importable;
    private User $user;
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
            $divipole = Divipole::updateOrCreate(
                ['id' => $row['id']],
                [
                    'municipality_id' => $row['municipio_id'],
                    'department_id'   => $row['departamento_id'],
                    'code'            => $row['codigo'],
                    'position_name'   => $row['nombre_puesto'],
                    'position_address'=> $row['direccion_puesto'],
                    'created_by'      => 1,
                    'updated_by'      => 1,
                    'updated_at'      => now(),
                ]
            );

            if (array_key_exists('usuario_asignado', $row)) {
                $raw = (string) ($row['usuario_asignado'] ?? '');
                $parts = preg_split('/[\s,;|\.]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $ids = collect($parts)
                    ->map(fn($v) => trim($v))
                    ->filter(fn($v) => $v !== '' && ctype_digit($v))
                    ->map(fn($v) => (int) $v)
                    ->unique()
                    ->values();

                if ($ids->isEmpty()) {
                    $divipole->users()->sync([]);
                    Log::info('Divipole users cleared due to empty usuario_asignado', [
                        'divipole_id' => $divipole->id,
                    ]);
                } else {
                    Log::info('Parsed usuario_asignado IDs', [
                        'divipole_id' => $divipole->id,
                        'raw'         => $raw,
                        'ids'         => $ids->all(),
                    ]);
                    $requested = $ids->all();
                    $existingIds = User::findMany($requested)->pluck('id')->all();
                    $missing = array_values(array_diff($requested, $existingIds));
                    if (!empty($missing)) {
                        Log::warning('Some user IDs from usuario_asignado do not exist', [
                            'divipole_id' => $divipole->id,
                            'requested'   => $requested,
                            'missing'     => $missing,
                        ]);
                    }
                    $divipole->users()->sync($existingIds);
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating/updating divipole or syncing users: ' . $e->getMessage() . ' | Row: ' . json_encode($row));
            return null;
        }
    }

    public function rules(): array
    {
        return [
            'id'               => 'required|integer',
            'departamento_id'  => 'required|integer|exists:departments,id',
            'municipio_id'     => 'required|integer|exists:municipalities,id',
            'codigo'           => 'required',
            'nombre_puesto'    => 'required',
            'direccion_puesto' => 'required',
            // Permite uno o varios IDs separados por coma, punto y coma, barra vertical, punto, o espacios
            'usuario_asignado' => [
                'nullable',
                'regex:/^\s*\d+(?:[\s,;|\.]+\d+)*\s*$/',
                function ($_, $value, $fail) {
                    if ($value === null) {
                        return;
                    }
                    $raw = (string) $value;
                    $parts = preg_split('/[\s,;|\.]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    $ids = collect($parts)
                        ->map(fn($v) => trim($v))
                        ->filter(fn($v) => $v !== '' && ctype_digit($v))
                        ->map(fn($v) => (int) $v)
                        ->unique()
                        ->values();

                    if ($ids->isEmpty()) {
                        return;
                    }
                    $requested = $ids->all();
                    $existingIds = User::findMany($requested)->pluck('id')->all();
                    $missing = array_values(array_diff($requested, $existingIds));
                    if (!empty($missing)) {
                        $fail('Los siguientes usuarios no existen: ' . implode(',', $missing));
                    }
                },
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id.required'                  => 'El campo id es obligatorio.',
            'id.integer'                   => 'El campo id debe ser numérico.',
            'departamento_id.required'     => 'El campo departamento_id es obligatorio.',
            'departamento_id.exists'       => 'El departamento_id no existe.',
            'municipio_id.required'        => 'El campo municipio_id es obligatorio.',
            'municipio_id.exists'          => 'El municipio_id no existe.',
            'usuario_asignado.regex'       => 'El usuario_asignado debe ser uno o varios IDs numéricos separados por coma, punto y coma, barra vertical, punto o espacios.',
        ];
    }
    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
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
