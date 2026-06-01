<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Client',
            'email' => 'client@e-client.com',
            'password' => bcrypt('password'),
            'role' => 'client',
            'is_validated' => true,
        ]);
    }
}
