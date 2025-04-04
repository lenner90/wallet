<?php

namespace App\Http\Controllers;

use App\Jobs\CalculateRebate;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01'
        ], [
            'wallet_id.required' => 'Please specify a wallet with ID',
            'wallet_id.exists' => 'The specified wallet does not exist',
            'amount.required' => 'Please specify an amount',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Minimum deposit amount is 0.01'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $wallet = Wallet::findOrFail($request->wallet_id);
        
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

    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01'
        ], [
            'wallet_id.required' => 'Please specify a wallet',
            'wallet_id.exists' => 'The specified wallet does not exist',
            'amount.required' => 'Please specify an amount',
            'amount.numeric' => 'Amount must be a number',
            'amount.min' => 'Minimum withdrawal amount is 0.01'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $wallet = Wallet::findOrFail($request->wallet_id);
        
        // Use a database transaction with pessimistic locking
        DB::beginTransaction();
        try {
            // Lock the wallet record for update to prevent concurrent modifications
            $wallet = Wallet::lockForUpdate()->findOrFail($request->wallet_id);
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

    public function balance($walletId)
    {
        try {
            $wallet = Wallet::findOrFail($walletId);
            return response()->json(['balance' => $wallet->balance]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }
    }

    public function transactions($walletId)
    {
        try {
            $wallet = Wallet::findOrFail($walletId);
            return response()->json($wallet->transactions()->latest()->get());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Wallet not found'
            ], 404);
        }
    }
}