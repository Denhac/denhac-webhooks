<?php

namespace Tests\Feature;

use App\Models\Customer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MemberCountApi extends TestCase
{
    #[Test]
    public function test_no_members(): void
    {
        Customer::truncate();

        $response = $this->get('/api/member_count');

        $response->assertStatus(200)
            ->assertJson([
                'members' => 0,
            ]);
    }

    #[Test]
    public function with_some_members(): void
    {
        Customer::truncate();

        // Create 3 members and 4 non-members
        Customer::factory(3)->member()->create();
        Customer::factory(4)->create();

        $response = $this->get('/api/member_count');

        $response->assertStatus(200)
            ->assertJson([
                'members' => 3,
            ]);
    }
}
