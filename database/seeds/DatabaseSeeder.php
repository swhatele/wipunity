<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
//      
        $this->call([
        UsersTableSeeder::class,
        user_deactivation_reasons::class,
        email_notification_types::class,
        in_app_notification_types::class,
        notification_types::class,
        poll_answer::class,
        report_reasons::class,
        basins::class,
    ]);
    }
}
