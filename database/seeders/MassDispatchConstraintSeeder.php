<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MassDispatchConstraintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('mass_dispatch_constraints')->insert([
            'start_time' => '08:00:00',
            'end_time' => '21:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
