<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;


Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'API is running'
    ]);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
});

Route::post('/orders', [OrderController::class, 'store']);
