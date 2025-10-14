<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'password' => bcrypt('password'),
            'college_id' => College::factory()->create([
                'code' => 'COE',
                'name' => 'College of Engineering',
                'logo' => 'college_of_engineering_logo.png',
            ]),
        ]);
    }
}
