<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== Create First User (Admin) ===');
        $this->command->newLine();

        $name = $this->command->ask('Enter user name');
        $email = $this->command->ask('Enter user email');
        $password = $this->command->secret('Enter password');
        $confirmPassword = $this->command->secret('Confirm password');

        if ($password !== $confirmPassword) {
            $this->command->error('Passwords do not match!');
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->command->newLine();
        $this->command->info("Admin user '{$name}' created successfully!");
    }
}
