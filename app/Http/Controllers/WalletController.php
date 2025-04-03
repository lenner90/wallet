<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateRebate;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function deposit(Request $request, Wallet $wallet)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        
        // Use a database transaction with pessimistic locking
        DB::beginTransaction();
        try {
            // Lock the wallet record for update to prevent concurrent modifications
            $lockedWallet = Wallet::lockForUpdate()->findOrFail($wallet->id);
            $lockedWallet->deposit($request->amount);
            
            Transaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => 'deposit',
                'amount' => $request->amount
            ]);
            
            DB::commit();
            
            // Dispatch rebate calculation after transaction is committed
            CalculateRebate::dispatch($lockedWallet, $request->amount);
            
            return response()->json(['message' => 'Deposit successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Deposit failed: ' . $e->getMessage()], 500);
        }
    }

    public function withdraw(Request $request, $walletId)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01']);
        
        // Use a database transaction with pessimistic locking
        DB::beginTransaction();
        try {
            // Lock the wallet record for update to prevent concurrent modifications
            $wallet = Wallet::lockForUpdate()->findOrFail($walletId);
            $wallet->withdraw($request->amount);
            
            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdrawal',
                'amount' => $request->amount
            ]);
            
            DB::commit();
            return response()->json(['message' => 'Withdrawal successful']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function balance(Wallet $wallet)
    {
        return response()->json(['balance' => $wallet->balance]);
    }

    public function transactions(Wallet $wallet)
    {
        return response()->json($wallet->transactions()->latest()->get());
    }
}