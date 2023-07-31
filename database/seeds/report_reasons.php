<?php

use Illuminate\Database\Seeder;

class report_reasons extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('report_reasons')->truncate();

        DB::table('report_reasons')->insert([
            ['title' => 'Spam'],
            ['title' => 'Copy Righted or Illegal Content'],
            ['title' => 'Hare Speech and Discrimination'],
            ['title' => 'Bullying and Harrassment'],
            ['title' => 'Invasion of Privacy'],
            ['title' => 'Factually Incorrect'],
            ['title' => 'Other'],
        ]);
    }
}
