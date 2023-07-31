<?php

use Illuminate\Database\Seeder;

class email_notification_types extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('email_notification_types')->truncate();

        DB::table('email_notification_types')->insert([
            ['type' => 'When I\'m tagged in a discussion'],
            ['type' => 'When an event gets scheduled from a conversation I partcipated in'],
            ['type' => 'New Features Added'],
        ]);
    }
}
