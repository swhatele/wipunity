<?php

use Illuminate\Database\Seeder;

class notification_types extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('notification_types')->truncate();

        DB::table('notification_types')->insert([
            ['type' => 'Real-Time'],
            ['type' => 'Daily'],
            ['type' => 'Weekly'],
            ['type' => 'Monthly'],
            ['type' => 'I do not want to recieve e-mails'],
        ]);
    }
}
