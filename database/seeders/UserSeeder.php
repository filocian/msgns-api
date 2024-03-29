<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private $table = 'users';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        DB::table($this->table)->insert([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
            'password' => Hash::make('daPassword123!')
        ]);
    }
}
