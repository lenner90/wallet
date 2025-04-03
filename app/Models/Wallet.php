<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * Deposit amount to wallet
     *
     * @param float $amount
     * @return bool
     */
    public function deposit($amount)
    {
        $this->balance += $amount;
        return $this->save();
    }

    /**
     * Withdraw amount from wallet
     *
     * @param float $amount
     * @return bool
     * @throws \Exception
     */
    public function withdraw($amount)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient funds');
        }
        
        $this->balance -= $amount;
        return $this->save();
    }

    /**
     * Get the transactions for the wallet.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}