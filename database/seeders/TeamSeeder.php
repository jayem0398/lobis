<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('teams')->truncate();

        $teams = [
            [
                'id' => 1,
                'team_name' => 'Titus', // (BPO Accounts)
                'quota' => 500000.00, 
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'team_name' => 'Jeremiah', // (Regular Accounts)
                'quota' => 500000.00,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('teams')->insert($teams);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}