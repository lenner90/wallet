<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AuthController;

// User registration route
Route::post('/register', [AuthController::class, 'register']);

Route::prefix('wallets')->group(function () {
    Route::post('/deposit', [WalletController::class, 'deposit']);
    Route::post('/withdraw', [WalletController::class, 'withdraw']);
    Route::get('/{wallet}/balance', [WalletController::class, 'balance']);
    Route::get('/{wallet}/transactions', [WalletController::class, 'transactions']);
});

Route::get('/greeting', function () {
    return 'Hello World';
});