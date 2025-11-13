<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Orchid\Platform\Models\Role;
use Orchid\Support\Facades\Dashboard;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolSeeder extends Seeder
{
    private const ALLOWED_KEYS = [
        'supervisor' => [
            'platform.index',
            'platform.systems.attachment',
            'platform.systems.users',
            'platform.systems.user.create',
            'platform.systems.user.edit',
            'platform.systems.user.delete',
            'platform.systems.Device',
            'platform.systems.Device.create',
            'platform.systems.Device.delete',
            'platform.systems.Device.show-all',
            'platform.systems.certify-packaging',
            'platform.systems.certify-packaging.cancel',
            'platform.systems.print-labels',
            'platform.systems.report-download',
            'platform.systems.material-delivery',
        ],
        'coordinator' => [
            'platform.index',
            'platform.systems.attachment',
            'platform.systems.users',
            'platform.systems.user.create',
            'platform.systems.user.edit',
            'platform.systems.material-delivery',
        ],
        'leader' => [
            'platform.index',
            'platform.systems.attachment',
            'platform.systems.users',
            'platform.systems.user.create',
            'platform.systems.material-delivery',
        ],
        'collaborator' => [
            'platform.index',
            'platform.systems.attachment',
            'platform.systems.Device',
            'platform.systems.Device.create',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin' => [
                'name' => 'Administrador',
                'permissions' => Dashboard::getAllowAllPermission(),
            ],
            /* 'supervisor' => [
                'name' => 'Supervisor',
                'permissions' => $this->getFilteredPermissions(self::ALLOWED_KEYS['supervisor']),
            ],
            'coordinator' => [
                'name' => 'Coordinador',
                'permissions' => $this->getFilteredPermissions(self::ALLOWED_KEYS['coordinator']),
            ],
            'leader' => [
                'name' => 'Lider',
                'permissions' => $this->getFilteredPermissions(self::ALLOWED_KEYS['leader']),
            ],
            'collaborator' => [
                'name' => 'Colaborador',
                'permissions' => $this->getFilteredPermissions(self::ALLOWED_KEYS['collaborator']),
            ], */
        ];

        foreach ($roles as $slug => $role) {
            $this->createOrUpdateRole($slug, $role['name'], $role['permissions']);
        }
    }

    /**
     * Create or update a role.
     *
     * @param string $slug
     * @param string $name
     * @param iterable $permissions
     */
    private function createOrUpdateRole(string $slug, string $name, iterable $permissions): void
    {
        try {
            Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'permissions' => $permissions,
                ]
            );
            $this->command->info(__("Role '{$name}' created or updated successfully."));
        } catch (\Exception $e) {
            $this->command->error(__("Error creating or updating role '{$name}': ") . $e->getMessage());
        }
    }

    /**
     * Get filtered permissions based on allowed keys.
     *
     * @param array $allowedKeys
     * @return iterable
     */
    private function getFilteredPermissions(array $allowedKeys): iterable
    {
        $permissions = Dashboard::getAllowAllPermission();
        foreach ($permissions as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                $permissions[$key] = false;
            }
        }
        return $permissions;
    }
}
