<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Customer::exists()) {
            Log::info('CustomerSeeder: Customer table is not empty. Skipping seeeding.');

            return;
        }
        Log::info('CustomerSeeder: Seeding Customers and UserMemberships.');

        $superuser = Customer::factory()
            ->create([
                'id' => 0,
                'username' => 'super',
                'first_name' => 'Super',
                'last_name' => 'User',
                'email' => 'super@dev.denhac.org',
                'member' => true,
                'id_checked' => true,
            ]);

        $boarduser = Customer::factory()->create([
            'id' => 1,
            'username' => 'board',
            'first_name' => 'Board',
            'last_name' => 'User',
            'email' => 'boardmember@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        $metatrainer = Customer::factory()->create([
            'id' => 2,
            'username' => 'metatrainer',
            'first_name' => 'Meta',
            'last_name' => 'Trainer',
            'email' => 'metatrainer@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        $ops = Customer::factory()->create([
            'id' => 3,
            'username' => 'ops',
            'first_name' => 'Ops',
            'last_name' => 'Manager',
            'email' => 'ops@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        $woodtrainer = Customer::factory()->create([
            'id' => 4,
            'username' => 'woodtrainer',
            'first_name' => 'Woodshop',
            'last_name' => 'Trainer',
            'email' => 'wood@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        $textilestrainer = Customer::factory()->create([
            'id' => 5,
            'username' => 'textilestrainer',
            'first_name' => 'Textiles',
            'last_name' => 'Trainer',
            'email' => 'fabric@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        Customer::factory()->create([
            'id' => 6,
            'username' => 'member',
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'a.member@dev.denhac.org',
            'member' => true,
            'id_checked' => true,
        ]);

        Customer::factory()->create([
            'id' => 7,
            'username' => 'lurker',
            'first_name' => 'Inactive',
            'last_name' => 'User',
            'email' => 'not.a.member@dev.denhac.org',
            'member' => false,
            'id_checked' => true,
        ]);

        Customer::factory()->count(100)->create();

        $memberships = [
            [$superuser, [
                UserMembership::MEMBERSHIP_FULL_MEMBER,
                UserMembership::MEMBERSHIP_BOARD,
                UserMembership::MEMBERSHIP_OPS_MANAGER,
                UserMembership::MEMBERSHIP_SAFETY_MANAGER,
                UserMembership::MEMBERSHIP_BUSINESS_MANAGER,
                UserMembership::MEMBERSHIP_EVENTS_MANAGER,
                UserMembership::MEMBERSHIP_TREASURER,
                UserMembership::MEMBERSHIP_3DP_TRAINER,
                UserMembership::MEMBERSHIP_3DP_USER,
                // Magic numbers from TrainableEquipmentSeeder
                201, 202, 203, 204, 205, 206,
                301, 302, 303, 304, 305, 306,
            ]],
            [$boarduser, [UserMembership::MEMBERSHIP_BOARD]],
            [$metatrainer, [UserMembership::MEMBERSHIP_META_TRAINER]],
            [$ops, [UserMembership::MEMBERSHIP_OPS_MANAGER]],
            [$woodtrainer, [201, 202, 203, 204, 301, 302, 303, 304]],
            [$textilestrainer, [205, 305]],

        ];

        foreach ($memberships as [$customerId, $planIds]) {
            foreach ($planIds as $planId) {
                UserMembership::factory()->create(['plan_id' => $planId, 'customer_id' => $customerId, 'status' => 'active']);
            }
        }
    }
}
