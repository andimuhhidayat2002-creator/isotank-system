<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@isotank.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create inspector user
        User::firstOrCreate(
            ['email' => 'inspector@isotank.com'],
            [
                'name' => 'Inspector User',
                'password' => Hash::make('password'),
                'role' => 'inspector',
            ]
        );

        // Create maintenance user
        User::firstOrCreate(
            ['email' => 'maintenance@isotank.com'],
            [
                'name' => 'Maintenance User',
                'password' => Hash::make('password'),
                'role' => 'maintenance',
            ]
        );

        // Create management user
        User::firstOrCreate(
            ['email' => 'management@isotank.com'],
            [
                'name' => 'Management User',
                'password' => Hash::make('password'),
                'role' => 'management',
            ]
        );


        // Create receiver (driver) user
        User::firstOrCreate(
            ['email' => 'driver@isotank.com'],
            [
                'name' => 'Driver User',
                'password' => Hash::make('password'),
                'role' => 'receiver',
            ]
        );

        // Create additional receiver user
        User::firstOrCreate(
            ['email' => 'receiver@isotank.com'],
            [
                'name' => 'Receiver User',
                'password' => Hash::make('password'),
                'role' => 'receiver',
            ]
        );

        // Create yard operator
        User::firstOrCreate(
            ['email' => 'yard@isotank.com'],
            [
                'name' => 'Yard Operator',
                'password' => Hash::make('password'),
                'role' => 'yard_operator',
            ]
        );

        $this->command->info('Default users created successfully!');
        $this->command->info('Admin: admin@isotank.com / password');
        $this->command->info('Inspector: inspector@isotank.com / password');
        $this->command->info('Maintenance: maintenance@isotank.com / password');
        $this->command->info('Management: management@isotank.com / password');
        $this->command->info('Receiver: driver@isotank.com / password');
        $this->command->info('Receiver: receiver@isotank.com / password');
        $this->command->info('Yard Operator: yard@isotank.com / password');
    }
}
