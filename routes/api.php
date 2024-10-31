<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//login api
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

//logout api
Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

//products api
Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('auth:sanctum');
Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('auth:sanctum');
Route::post('/products/edit', [App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('auth:sanctum');
//categories api
Route::apiResource('/api-categories', App\Http\Controllers\Api\CategoryController::class)->middleware('auth:sanctum');

//orders api
Route::post('/save-order', [App\Http\Controllers\Api\OrderController::class, 'saveOrder'])->middleware('auth:sanctum');

//discounts api
Route::get('/api-discounts', [App\Http\Controllers\Api\DiscountController::class, 'index'])->middleware('auth:sanctum');

Route::post('/api-discounts', [App\Http\Controllers\Api\DiscountController::class, 'store'])->middleware('auth:sanctum');

// api resource report

Route::get('/orders/{date?}', [App\Http\Controllers\Api\OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('/summary/{date?}', [App\Http\Controllers\Api\OrderController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/order-item/{date?}', [App\Http\Controllers\Api\OrderItemController::class, 'index'])->middleware('auth:sanctum');
Route::get('/order-sales', [App\Http\Controllers\Api\OrderItemController::class, 'orderSales'])->middleware('auth:sanctum');