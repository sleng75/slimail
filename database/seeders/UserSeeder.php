<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create tenant for SLIMAT
        $slimatTenant = Tenant::firstOrCreate(
            ['slug' => 'slimat'],
            [
                'name' => 'SLIMAT',
                'email' => 'contact@slimat.com',
                'phone' => '+226 00 00 00 00',
                'address' => 'Ouagadougou',
                'city' => 'Ouagadougou',
                'country' => 'BF',
                'timezone' => 'Africa/Ouagadougou',
                'locale' => 'fr',
                'settings' => [],
                'status' => 'active',
            ]
        );

        // Create user slmty109@gmail.com
        User::firstOrCreate(
            ['email' => 'slmty109@gmail.com'],
            [
                'tenant_id' => $slimatTenant->id,
                'name' => 'SLIMAT Admin',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Create second tenant for ousweb51
        $ousweb51Tenant = Tenant::firstOrCreate(
            ['slug' => 'ousweb51'],
            [
                'name' => 'Ousweb51',
                'email' => 'ousweb51@gmail.com',
                'phone' => '+226 00 00 00 01',
                'address' => 'Ouagadougou',
                'city' => 'Ouagadougou',
                'country' => 'BF',
                'timezone' => 'Africa/Ouagadougou',
                'locale' => 'fr',
                'settings' => [],
                'status' => 'active',
            ]
        );

        // Create user ousweb51@gmail.com
        User::firstOrCreate(
            ['email' => 'ousweb51@gmail.com'],
            [
                'tenant_id' => $ousweb51Tenant->id,
                'name' => 'Ousweb51 Admin',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_super_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Users created successfully:');
        $this->command->info('- slmty109@gmail.com (Super Admin, password: password)');
        $this->command->info('- ousweb51@gmail.com (Owner, password: password)');
    }
}
