<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\TrainableEquipmentSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(TrainableEquipmentSeeder::class);
        $this->call(CustomerSeeder::class);
    }
}
