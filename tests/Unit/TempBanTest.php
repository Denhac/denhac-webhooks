<?php

namespace Tests\Unit;

use App\TempBan;
use Carbon\Carbon;
use Tests\TestCase;

class TempBanTest extends TestCase
{
    /** @test */
    public function isBanned_returns_true_if_row_exists_with_both_user_and_channel()
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertTrue(TempBan::isBanned('foo_user', 'foo_channel'));
    }

    /** @test */
    public function isBanned_returns_false_if_row_only_has_user()
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertFalse(TempBan::isBanned('foo_user', 'bar_channel'));
    }

    /** @test */
    public function isBanned_returns_false_if_row_only_has_channel()
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertFalse(TempBan::isBanned('bar_user', 'foo_channel'));
    }

    /** @test */
    public function isBanned_before_expiration_time_returns_true()
    {

        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
            'expires_at' => Carbon::now()->addMinute(),
        ]);

        $this->assertTrue(TempBan::isBanned('foo_user', 'foo_channel'));
    }

    /** @test */
    public function isBanned_after_expiration_time_returns_false()
    {

        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->assertFalse(TempBan::isBanned('foo_user', 'foo_channel'));
    }
}
