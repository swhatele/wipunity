<?php

use Illuminate\Database\Seeder;

class basins extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('basins')->truncate();

        DB::table('basins')->insert([
            ['basin_name' => 'Lake Erie', 'basin_color_code' => '#DC353C'],
            ['basin_name' => 'Allegheny River', 'basin_color_code' => '#8F2041'],
            ['basin_name' => 'Lake Ontario and Minor Tributaries', 'basin_color_code' => '#884B00'],
            ['basin_name' => 'Chemung River', 'basin_color_code' => '#9D2F00'],
            ['basin_name' => 'Black River', 'basin_color_code' => '#007351'],
            ['basin_name' => 'Seneca-Oneida-Oswego River', 'basin_color_code' => '#458400'],
            ['basin_name' => 'St. Lawrence River', 'basin_color_code' => '#858800'],
            ['basin_name' => 'Lake Champlain', 'basin_color_code' => '#006979'],
            ['basin_name' => 'Upper Hudson River', 'basin_color_code' => '#004F8C'],
            ['basin_name' => 'Mohawk River', 'basin_color_code' => '#200087'],
            ['basin_name' => 'Genesee River', 'basin_color_code' => '#4E0078'],
            ['basin_name' => 'Delaware River', 'basin_color_code' => '#AC00A4'],
            ['basin_name' => 'Lower Hudson River', 'basin_color_code' => '#7C099B'],
            ['basin_name' => 'Passaic-Newark (Rampo River)', 'basin_color_code' => '#DC2481'],
             ['basin_name' => 'Housatonic River', 'basin_color_code' => '#9C9C9C'],
             ['basin_name' => 'Atlantic Ocean', 'basin_color_code' => '#5B5B5B'],
        ]);
    }

}
