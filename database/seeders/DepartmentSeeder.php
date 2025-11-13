<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'ANTIOQUIA'         , 'code' => 1 ],
            ['name' => 'ATLANTICO'         , 'code' => 3 ],
            ['name' => 'BOLIVAR'           , 'code' => 5 ],
            ['name' => 'BOYACA'            , 'code' => 7 ],
            ['name' => 'CALDAS'            , 'code' => 9 ],
            ['name' => 'CAUCA'             , 'code' => 11],
            ['name' => 'CESAR'             , 'code' => 12],
            ['name' => 'CORDOBA'           , 'code' => 13],
            ['name' => 'CUNDINAMARCA'      , 'code' => 15],
            ['name' => 'BOGOTA D.C.'       , 'code' => 16],
            ['name' => 'CHOCO'             , 'code' => 17],
            ['name' => 'HUILA'             , 'code' => 19],
            ['name' => 'MAGDALENA'         , 'code' => 21],
            ['name' => 'NARIÑO'            , 'code' => 23],
            ['name' => 'RISARALDA'         , 'code' => 24],
            ['name' => 'NORTE DE SANTANDER', 'code' => 25],
            ['name' => 'QUINDIO'           , 'code' => 26],
            ['name' => 'SANTANDER'         , 'code' => 27],
            ['name' => 'SUCRE'             , 'code' => 28],
            ['name' => 'TOLIMA'            , 'code' => 29],
            ['name' => 'VALLE'             , 'code' => 31],
            ['name' => 'ARAUCA'            , 'code' => 40],
            ['name' => 'CAQUETA'           , 'code' => 44],
            ['name' => 'CASANARE'          , 'code' => 46],
            ['name' => 'LA GUAJIRA'        , 'code' => 48],
            ['name' => 'GUAINIA'           , 'code' => 50],
            ['name' => 'META'              , 'code' => 52],
            ['name' => 'GUAVIARE'          , 'code' => 54],
            ['name' => 'SAN ANDRES'        , 'code' => 56],
            ['name' => 'AMAZONAS'          , 'code' => 60],
            ['name' => 'PUTUMAYO'          , 'code' => 64],
            ['name' => 'VAUPES'            , 'code' => 68],
            ['name' => 'VICHADA'           , 'code' => 72],
        ];

        foreach ($departments as $department) {
            $this->createOrUpdateDepartment($department);
        }
        $this->command->info(__('Departments records created successfully'));

    }

    private function createOrUpdateDepartment($department)
    {
        try {
            Department::updateOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']]
            );
            $this->command->info(__("Department :department record successfully", ['department' => $department['name']]));
        } catch (\Exception $e) {
            $this->command->error(__("Error registering department :department : :error", ['department' => $department['name'], 'error' => $e->getMessage()]));
        }
    }
}
