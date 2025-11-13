<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        try {
            $users = [
                [
                    'name' => env('ADMIN_NAME', 'Super administrador'),
                    'email' => env('ADMIN_EMAIL', 'superadmin@reporteqr.com'),
                    'password' => bcrypt(env('ADMIN_PASSWORD', '2042773jD$')),
                ],
            ];

            foreach ($users as $user) {
                if (!User::where('email', $user['email'])->exists()) {
                    User::updateOrCreate($user);
                }
            }
            $admin = User::firstWhere('email', env('ADMIN_EMAIL', 'superadmin@digitalizacion.com'));
            if (($role = Role::firstWhere('slug', 'superadmin'))
                    && !$admin->inRole($role)) {
                    $admin->addRole($role);
            }
            $this->command->info(__('Users created or updated successfully.'));
        } catch (\Exception $e) {
            $this->command->error('Error creating or updating users: ' . $e->getMessage());
        }
    }
}
