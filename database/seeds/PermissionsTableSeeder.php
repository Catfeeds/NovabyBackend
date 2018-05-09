<?php

use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = $this->read_csv_lines('permissions.csv');
        \Illuminate\Support\Facades\DB::table('permissions')->insert($datas);
    }
}
