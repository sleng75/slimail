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
        $this->command->info('Seeding SliMail database...');
        $this->command->newLine();

        // 1. Roles and permissions configuration
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Plans (subscription tiers)
        $this->call(PlanSeeder::class);

        // 3. System email templates
        $this->call(SystemTemplateSeeder::class);

        // 4. Template library (professional pre-built templates)
        $this->call(TemplateLibrarySeeder::class);

        // 5. Default users (optional - can be run separately)
        $this->call(UserSeeder::class);

        $this->command->newLine();
        $this->command->info('Database seeding completed successfully!');
    }
}
