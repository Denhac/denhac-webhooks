<?php

namespace Tests\Unit;

use App\Models\TempBan;
use Carbon\Carbon;
use Tests\TestCase;

class TempBanTest extends TestCase
{
    /** @test */
    public function is_banned_returns_true_if_row_exists_with_both_user_and_channel(): void
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertTrue(TempBan::isBanned('foo_user', 'foo_channel'));
    }

    /** @test */
    public function is_banned_returns_false_if_row_only_has_user(): void
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertFalse(TempBan::isBanned('foo_user', 'bar_channel'));
    }

    /** @test */
    public function is_banned_returns_false_if_row_only_has_channel(): void
    {
        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
        ]);

        $this->assertFalse(TempBan::isBanned('bar_user', 'foo_channel'));
    }

    /** @test */
    public function is_banned_before_expiration_time_returns_true(): void
    {

        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
            'expires_at' => Carbon::now()->addMinute(),
        ]);

        $this->assertTrue(TempBan::isBanned('foo_user', 'foo_channel'));
    }

    /** @test */
    public function is_banned_after_expiration_time_returns_false(): void
    {

        TempBan::create([
            'user_id' => 'foo_user',
            'channel_id' => 'foo_channel',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->assertFalse(TempBan::isBanned('foo_user', 'foo_channel'));
    }
}
