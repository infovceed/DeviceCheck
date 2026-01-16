<?php

namespace App\Imports;

use App\Models\Department;
use Log;
use App\Models\User;
use Orchid\Platform\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UsersImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts,ShouldQueue
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
            if($row['id'] == null || $row['id'] == ''|| $row['id'] == 1) {
                Log::warning('Skipping row with missing ID', ['row' => $row]);
                return null;
            }
            $departmentId = Department::where('id', $row['departamento'])->first()->id;
            // Construye datos sin el id para evitar que Eloquent ignore el PK en create
            $newUser = [
                'name'           => $row['nombre'],
                'document'       => $row['documento'],
                'department_id'  => $departmentId,
                'email'          => $row['email'],
                'password'       => bcrypt($row['documento']),
            ];

            // Si el usuario con ese ID existe, actualizar; si no, crear forzando el ID
            $user = User::find($row['id']);
            if ($user) {
                $user->fill($newUser)->save();
            } else {
                $user = new User($newUser);
                // Forzar el ID explícito del Excel
                $user->id = (int) $row['id'];
                $user->save();
            }
            if (!$row['rol']) {
                $role = Role::where('slug', 'operador')->first();
                $user->roles()->attach($role->id);
            }
            if ($row['rol']) {
                $role = Role::where('id', $row['rol'])->first();
                $user->replaceRoles([$role->id]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing row', ['row' => $row, 'error' => $e->getMessage()]);
            return null;
        }
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
        $rules = array_combine(array_map(function ($el) use ($renameMap) {
            return $renameMap[$el];
        }, array_keys($array)), array_values($array));
        return $rules;
    }

    public function customValidationMessages()
    {
        return [
            'user.name.required'            => 'El campo NOMBRE es obligatorio.',
            'user.document.required'        => 'El campo DOCUMENTO es obligatorio.',
            'user.department_id.required'   => 'El campo DEPARTAMENTO es obligatorio.',
            'user.email.required'           => 'El campo EMAIL es obligatorio.',
            'user.password.required'        => 'El campo PASSWORD es obligatorio.',
        ];
    }

}
