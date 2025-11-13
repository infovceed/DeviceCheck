<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info(__('Do you want to run migrations for departments, municipalities?'));
        $this->command->info(__('Press Y for yes or N for no'));
        $answer = $this->command->ask(__('Answer'));
        $seeders=[
            RolSeeder::class,
            UserSeeder::class,
            CorporationSeeder::class,
        ];
        if ($answer == 'Y') {
            $seeders[] = DepartmentSeeder::class;
            $seeders[] = MunicipalitySeeder::class;
        }
    
        $this->call($seeders);
    }
}
