<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Benutzer einfügen
        DB::table('users')->insert([
            [
                'username' => 'Admin',
                'email' => env('ADMIN_EMAIL'),
                'password' => bcrypt(env('ADMIN_PASSWORD')),
                'last_name' => 'Media Intelligence',
                'first_name' => 'Admin',
                'is_admin' => 1,
                'user_uid' => uniqid(),
                'email_verified_at' => Carbon::now()
            ]
        ]);
    }
}
