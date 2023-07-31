<?php

use Illuminate\Database\Seeder;

class in_app_notification_types extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('in_app_notification_types')->truncate();

        DB::table('in_app_notification_types')->insert([
            ['type' => 'Activity related to a discussion I started'],
            ['type' => 'Activity related to a discussion I follow'],
            ['type' => 'When I\'m tagged in a discussion'],
        ]);
    }
}
