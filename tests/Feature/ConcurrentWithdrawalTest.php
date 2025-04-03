<?php

namespace Tests\Feature;

use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConcurrentWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_withdrawals()
    {
        $wallet = Wallet::factory()->create(['balance' => 100.00]);
        $url = route('wallet.withdraw', ['wallet' => $wallet->id]);

        $responses = Http::pool(function ($pool) use ($url) {
            return [
                $pool->as('first')->post($url, ['amount' => 60.00]),
                $pool->as('second')->post($url, ['amount' => 60.00]),
                $pool->as('third')->post($url, ['amount' => 60.00]),
            ];
        });

        // Assert only one withdrawal succeeded
        $successCount = 0;
        $failureCount = 0;

        foreach ($responses as $response) {
            if ($response->successful()) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        $this->assertEquals(1, $successCount);
        $this->assertEquals(2, $failureCount);
        $this->assertEquals(40.00, $wallet->fresh()->balance);
    }
}