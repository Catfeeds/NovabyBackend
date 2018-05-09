<?php

use Illuminate\Database\Seeder;

class RolePermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = $this->read_csv_lines('role_permissions.csv');
        \Illuminate\Support\Facades\DB::table('role_permissions')->insert($datas);
    }
}
