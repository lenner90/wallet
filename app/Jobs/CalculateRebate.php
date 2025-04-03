<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateRebate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Wallet $wallet,
        public float $amount
    ) {}

    public function handle()
    {
        $rebateAmount = $this->amount * 0.01;
        
        // Use database transaction with pessimistic locking
        DB::beginTransaction();
        try {
            // Lock the wallet record for update to prevent concurrent modifications
            $lockedWallet = Wallet::lockForUpdate()->findOrFail($this->wallet->id);
            $lockedWallet->deposit($rebateAmount);
            
            Transaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'rebate',
                'amount' => $rebateAmount
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->fail($e);
        }
    }
}