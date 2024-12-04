<?php

use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); 
Route::post('/getUserStatus', [AuthController::class, 'getUserStatus']); 
Route::post('/webhook/lemonsqueezy', [AuthController::class, 'handleWebhook']);
Route::middleware('auth')->post('/getCheckoutUrl', [AuthController::class, 'getCheckoutUrl']); 
