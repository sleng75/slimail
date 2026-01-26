<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class SystemTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load templates from separate files
        $templateFiles = [
            __DIR__ . '/templates/welcome.php',
            __DIR__ . '/templates/newsletter.php',
            __DIR__ . '/templates/promotional.php',
            __DIR__ . '/templates/transactional.php',
            __DIR__ . '/templates/other.php',
        ];

        $allTemplates = [];

        foreach ($templateFiles as $file) {
            if (file_exists($file)) {
                $templates = require $file;
                $allTemplates = array_merge($allTemplates, $templates);
            }
        }

        // Create or update each template
        foreach ($allTemplates as $templateData) {
            EmailTemplate::withoutGlobalScopes()->updateOrCreate(
                ['name' => $templateData['name'], 'is_system' => true],
                [
                    'name' => $templateData['name'],
                    'description' => $templateData['description'],
                    'category' => $templateData['category'],
                    'default_subject' => $templateData['default_subject'],
                    'html_content' => $templateData['html_content'],
                    'is_system' => true,
                    'is_active' => true,
                    'tenant_id' => null,
                    'created_by' => null,
                ]
            );
        }

        $this->command->info('✓ ' . count($allTemplates) . ' templates système créés/mis à jour.');
    }
}
