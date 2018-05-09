<?php

use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = $this->read_csv_lines('roles.csv');
        \Illuminate\Support\Facades\DB::table('roles')->insert($datas);
    }
}
