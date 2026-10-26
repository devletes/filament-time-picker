<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Workbench\App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // updateOrCreate: testbench's workbench.user may already have created the account.
        User::query()->updateOrCreate(['email' => 'salman@example.com'], [
            'name' => 'Salman Hijazi',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
