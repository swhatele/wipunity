<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::table('users')->insert([
           'name' => 'admin',
           'email' => 'admin@tnc.org',
           'password' => Hash::make('123456789'),
          'role'=>1,
          ]);
    }
}
