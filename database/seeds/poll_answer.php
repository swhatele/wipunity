<?php

use Illuminate\Database\Seeder;

class poll_answer extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('poll_answer')->truncate();

        DB::table('poll_answer')->insert([
            ['answer' => 'Yes'],
            ['answer' => 'No'],
            ['answer' => 'Strongly Agree'],
            ['answer' => 'Agree'],
            ['answer' => 'Neutral'],
            ['answer' => 'Disagree'],
            ['answer' => 'Strongly Disagree'],
        ]);
    }
}
