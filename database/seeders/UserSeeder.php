<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();

        $users = [
            [
                'id' => 1,
                'name' => 'Administrator IT Admin',
                'email' => 'admin@dev.ph',
                'password' => Hash::make('admin@0330'),
                'role' => 'admin',
                'team_id' => null, 
                'created_at' => '2024-06-25 00:00:00',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 12,
                'name' => 'Chrizzia Lyn Naldo',
                'email' => 'chrizzialyn.naldo@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 2,
                'created_at' => '2024-08-30 13:43:52',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 14,
                'name' => 'Carlos Caberto Jr.',
                'email' => 'carlos.caberto@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 2,
                'created_at' => '2024-09-24 13:57:32',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 16,
                'name' => 'Conrado Villena Jr.',
                'email' => 'conrad.villena@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:20:21',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 17,
                'name' => 'Dennis Gayapa',
                'email' => 'dennis.gayapa@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:21:21',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 18,
                'name' => 'Juliet Bautista',
                'email' => 'juliet.bautista@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:22:02',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 19,
                'name' => 'Leny Palgan',
                'email' => 'leny.palgan@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 2,
                'created_at' => '2024-10-02 13:22:59',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 20,
                'name' => 'Loida Carino',
                'email' => 'loida.carino@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 2,
                'created_at' => '2024-10-02 13:23:41',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 21,
                'name' => 'Marienel Uchi',
                'email' => 'marienel.uchi@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 1,
                'created_at' => '2024-10-02 13:24:47',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 22,
                'name' => 'Rowena Quevedo',
                'email' => 'rowena.quevedo@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:26:19',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 23,
                'name' => 'Karen Lavina',
                'email' => 'karen.lavina@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 1,
                'created_at' => '2024-10-02 13:27:13',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 25,
                'name' => 'Sarah Jane Acala',
                'email' => 'sarahjane.acala@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 1,
                'created_at' => '2024-10-02 13:28:58',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 26,
                'name' => 'Delia Javier',
                'email' => 'delia.javier@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:30:32',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 27,
                'name' => 'Janica Laurente',
                'email' => 'janica.laurente@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'admin',
                'team_id' => null,
                'created_at' => '2024-10-02 13:30:32',
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 28,
                'name' => 'Jecelle Manligoy',
                'email' => 'jecelle.manligoy@lendell.ph',
                'password' => Hash::make('12345'),
                'role' => 'user',
                'team_id' => 2,
                'created_at' => '2026-02-26 17:20:53',
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('users')->insert($users);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}