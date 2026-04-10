<?php

namespace App\Imports;

use App\Jobs\NotifyUserOfImportError;
use App\Models\Department;
use App\Models\User;
use App\Notifications\DashboardNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Orchid\Platform\Models\Role;

class UsersImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, ShouldQueue, WithValidation, SkipsOnFailure
{
    use Importable;
    private User $user;

    /**
     * La validación de Excel usa como índice el número real de fila del archivo.
     * Guardamos acá las filas para poder tomar el ID y así ignorarlo en reglas "unique" al editar.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $rowsForValidation = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index)
    {
        $this->rowsForValidation[(int) $index] = $data;

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $importingUser = $this->user;

        try {
            $rowId = $this->parsePositiveInt($row['id'] ?? null);
            if ($rowId === null || $rowId === 1) {
                Log::warning('Skipping row with missing ID', ['row' => $row]);
                return null;
            }

            $departmentId = Department::where('id', $row['departamento'])->first()->id;
            $payload = [
                'name'          => $row['nombre'],
                'document'      => $row['documento'],
                'department_id' => $departmentId,
                'email'         => $row['email'],
                'password'      => bcrypt($row['documento']),
            ];

            $targetUser = User::find($rowId);
            if ($targetUser) {
                $targetUser->fill($payload)->save();
            } else {
                $targetUser = new User($payload);
                $targetUser->id = $rowId;
                $targetUser->save();
            }

            if (!$row['rol']) {
                $role = Role::where('slug', 'operador')->first();
                $targetUser->roles()->attach($role->id);
            }

            if ($row['rol']) {
                $role = Role::where('id', $row['rol'])->first();
                $targetUser->replaceRoles([$role->id]);
            }

            // Import con WithBatchInserts hace INSERT masivo y no soporta UPDATE.
            // Como persistimos manualmente (create/update + roles), devolvemos null
            // para que el paquete no intente insertar nuevamente el registro.
            return null;
        } catch (\Exception $e) {
            Log::error('Error processing row', ['row' => $row, 'error' => $e->getMessage()]);

            $importingUser->notify(new DashboardNotification(
                'Error en importación de usuarios',
                'La fila: ' . json_encode($row) . ' presentó un error y fue omitida.'
            ));

            return null;
        }
    }

    /**
     * @param mixed $value
     */
    private function parsePositiveInt($value): ?int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($intValue === false) {
            return null;
        }

        if ($intValue <= 0) {
            return null;
        }

        return (int) $intValue;
    }

    public function headingRow(): int
    {
        return 1;
    }
    public function chunkSize(): int
    {
        return 1000;
    }
    public function batchSize(): int
    {
        return 1000;
    }

    public function rules(): array
    {
        $array = User::$rules;
        $renameMap = [
            'user.id'              => 'id',
            'user.name'            => 'nombre',
            'user.document'        => 'documento',
            'user.department_id'   => 'departamento',
            'user.email'           => 'email',
        ];

        $rules = array_combine(
            array_map(function ($el) use ($renameMap) {
                return $renameMap[$el];
            }, array_keys($array)),
            array_values($array)
        );

        // El ID=1 (admin/sistema) no debe ser modificable por importación.
        $rules['id'] = $this->appendRule($rules['id'] ?? [], 'not_in:1');
        $rules['email'] = $this->replaceUniqueRule($rules['email'] ?? [], 'email', 'email');
        $rules['documento'] = $this->replaceUniqueRule($rules['documento'] ?? [], 'document', 'documento');

        return $rules;
    }

    /**
     * @param array<int, mixed>|string|callable|object $baseRules
     * @return array<int, mixed>
     */
    private function appendRule($baseRules, string $rule): array
    {
        $normalized = is_array($baseRules) ? $baseRules : [$baseRules];
        $normalized[] = $rule;

        return $normalized;
    }

    /**
     * @param array<int, mixed>|string|callable|object $baseRules
     * @return array<int, mixed>
     */
    private function replaceUniqueRule($baseRules, string $column, string $label): array
    {
        $normalized = is_array($baseRules) ? $baseRules : [$baseRules];
        $filtered = array_values(array_filter($normalized, static function ($rule) {
            return !is_string($rule) || !str_starts_with($rule, 'unique:');
        }));

        $filtered[] = function (string $attribute, $value, callable $fail) use ($column, $label): void {
            if ($value === null || $value === '') {
                return;
            }

            $excelRowNumber = (int) strtok($attribute, '.');
            $row = $this->rowsForValidation[$excelRowNumber] ?? [];
            $rowId = $this->parsePositiveInt($row['id'] ?? null);

            $query = User::query()->where($column, $value);
            if ($rowId !== null) {
                if ($rowId === 1) {
                    return;
                }

                $query->where('id', '!=', $rowId);
            }

            if ($query->exists()) {
                $fail("El valor del campo {$label} ya está en uso.");
            }
        };

        return $filtered;
    }

    public function customValidationMessages()
    {
        return [
            'id.not_in'             => 'El usuario con ID=1 no es modificable.',
            'nombre.required'         => 'El campo NOMBRE es obligatorio.',
            'documento.required'      => 'El campo DOCUMENTO es obligatorio.',
            'departamento.required'   => 'El campo DEPARTAMENTO es obligatorio.',
            'email.email'             => 'El campo EMAIL no tiene un formato válido.',
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
                'Error en importación de usuarios',
                "Fila {$row}: Campo '{$attribute}' - {$errors}"
            ));
        }
    }
}
