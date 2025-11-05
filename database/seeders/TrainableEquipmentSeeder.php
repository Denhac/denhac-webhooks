<?php

namespace Database\Seeders;

use App\Models\TrainableEquipment;
use App\Models\UserMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class TrainableEquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (TrainableEquipment::exists()) {
            Log::info('TrainableEquipmentSeeder: TrainableEquipment table is not empty. Skipping seeeding.');

            return;
        }
        Log::info('TrainableEquipmentSeeder: Seeding TrainableEquipment.');
        TrainableEquipment::create([
            'name' => 'Table Saw',
            'user_plan_id' => 301,
            'trainer_plan_id' => 201,
        ]);
        TrainableEquipment::create([
            'name' => 'Router Table',
            'user_plan_id' => 302,
            'trainer_plan_id' => 202,
        ]);
        TrainableEquipment::create([
            'name' => 'Jointer',
            'user_plan_id' => 303,
            'trainer_plan_id' => 203,
        ]);
        TrainableEquipment::create([
            'name' => 'Planer',
            'user_plan_id' => 304,
            'trainer_plan_id' => 204,
        ]);
        TrainableEquipment::create([
            'name' => 'Embroidery Machine',
            'user_plan_id' => 305,
            'trainer_plan_id' => 205,
        ]);
        TrainableEquipment::create([
            'name' => 'Lasers',
            'user_plan_id' => 306,
            'trainer_plan_id' => 206,
        ]);
        TrainableEquipment::create([
            'name' => '3D Printers',
            'user_plan_id' => UserMembership::MEMBERSHIP_3DP_USER,
            'trainer_plan_id' => UserMembership::MEMBERSHIP_3DP_TRAINER,
        ]);
    }
}
