<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Role definitions with their permissions.
     * These are used by the CheckPermission middleware.
     */
    protected array $roles = [
        'owner' => [
            'name' => 'Propriétaire',
            'description' => 'Accès complet à toutes les fonctionnalités',
            'permissions' => [
                'contacts.view',
                'contacts.manage',
                'campaigns.view',
                'campaigns.manage',
                'templates.manage',
                'automations.manage',
                'statistics.view',
                'api.manage',
                'billing.manage',
                'settings.manage',
                'users.manage',
            ],
        ],
        'admin' => [
            'name' => 'Administrateur',
            'description' => 'Accès complet sauf facturation',
            'permissions' => [
                'contacts.view',
                'contacts.manage',
                'campaigns.view',
                'campaigns.manage',
                'templates.manage',
                'automations.manage',
                'statistics.view',
                'api.manage',
                'settings.manage',
                'users.manage',
            ],
        ],
        'editor' => [
            'name' => 'Éditeur',
            'description' => 'Gestion des campagnes et templates',
            'permissions' => [
                'contacts.view',
                'contacts.manage',
                'campaigns.view',
                'campaigns.manage',
                'templates.manage',
                'statistics.view',
            ],
        ],
        'analyst' => [
            'name' => 'Analyste',
            'description' => 'Statistiques en lecture seule',
            'permissions' => [
                'contacts.view',
                'campaigns.view',
                'statistics.view',
            ],
        ],
        'developer' => [
            'name' => 'Développeur',
            'description' => 'Accès API uniquement',
            'permissions' => [
                'api.manage',
                'statistics.view',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('Roles and permissions configuration:');
        $this->command->newLine();

        foreach ($this->roles as $role => $config) {
            $this->command->line("  {$config['name']} ({$role}):");
            $this->command->line("    - {$config['description']}");
            $this->command->line("    - Permissions: " . count($config['permissions']));
        }

        $this->command->newLine();
        $this->command->info('Note: Roles are managed via the "role" column on users table.');
        $this->command->info('Permissions are checked dynamically by the CheckPermission middleware.');
    }

    /**
     * Get all available roles.
     */
    public static function getRoles(): array
    {
        return (new self())->roles;
    }

    /**
     * Get permissions for a specific role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        $instance = new self();
        return $instance->roles[$role]['permissions'] ?? [];
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = self::getPermissionsForRole($role);
        return in_array($permission, $permissions);
    }

    /**
     * Get all available permissions.
     */
    public static function getAllPermissions(): array
    {
        return [
            'contacts.view' => 'Voir les contacts',
            'contacts.manage' => 'Gérer les contacts',
            'campaigns.view' => 'Voir les campagnes',
            'campaigns.manage' => 'Gérer les campagnes',
            'templates.manage' => 'Gérer les templates',
            'automations.manage' => 'Gérer les automatisations',
            'statistics.view' => 'Voir les statistiques',
            'api.manage' => 'Gérer les API',
            'billing.manage' => 'Gérer la facturation',
            'settings.manage' => 'Gérer les paramètres',
            'users.manage' => 'Gérer les utilisateurs',
        ];
    }
}
