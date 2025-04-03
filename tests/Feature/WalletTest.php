<?php

namespace Tests\Feature;

use App\Jobs\CalculateRebate;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class WalletTest extends TestCase
{
    use DatabaseTransactions;

    public function test_deposit_with_rebate()
    {
        Bus::fake();
        
        $wallet = Wallet::factory()->create(['balance' => 100]);
        
        $response = $this->postJson("/api/wallets/{$wallet->id}/deposit", [
            'amount' => 100
        ]);
        
        $response->assertOk();
        $this->assertEquals(200, $wallet->fresh()->balance);
        
        Bus::assertDispatched(CalculateRebate::class);
    }

    public function test_concurrent_deposits()
    {
        Bus::fake();
        
        $wallet = Wallet::factory()->create(['balance' => 100]);
        $walletId = $wallet->id;
        
        // Simulate first transaction starting but not committing yet
        DB::beginTransaction();
        $this->postJson("/api/wallets/{$walletId}/deposit", ['amount' => 100]);
        
        // Simulate second transaction in a separate connection
        $pdo = DB::connection()->getPdo();
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + 200 WHERE id = ?");
        $stmt->execute([$walletId]);
        
       
        DB::commit();
        
        $this->assertEquals(400, $wallet->fresh()->balance);
        Bus::assertDispatchedTimes(CalculateRebate::class, 1); // Only one would be dispatched in this test
    }

    public function test_withdrawal_insufficient_funds()
    {
        $wallet = Wallet::factory()->create(['balance' => 100]);
        
        // Make sure the wallet exists in the database
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
        
        $response = $this->postJson("/api/wallets/{$wallet->id}/withdraw", [
            'amount' => 200
        ]);
        
        $response->assertStatus(400);
        $this->assertEquals(100, $wallet->fresh()->balance);
    }
}