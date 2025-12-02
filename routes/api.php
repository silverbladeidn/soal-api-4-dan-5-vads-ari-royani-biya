<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;

Route::post('/generate-token', [AuthController::class, 'generateToken']);
Route::post('/customer-items', [CustomerController::class, 'getCustomerItems']);
