<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DiscountController;
use App\Http\Controllers\Api\DraftOrderController;
use App\Http\Controllers\Api\HotelGuestController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableManagementController;
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

// login api
Route::post('/login', [AuthController::class, 'login']);

// logout api
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// products api
Route::get('/products', [ProductController::class, 'index'])->middleware('auth:sanctum');
Route::post('/products', [ProductController::class, 'store'])->middleware('auth:sanctum');
Route::post('/products/edit', [ProductController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->middleware('auth:sanctum');
// categories api
Route::apiResource('/api-categories', CategoryController::class)->middleware('auth:sanctum');

// orders api
Route::post('/save-order', [OrderController::class, 'saveOrder'])->middleware('auth:sanctum');

// discounts api
Route::get('/api-discounts', [DiscountController::class, 'index'])->middleware('auth:sanctum');

Route::post('/api-discounts', [DiscountController::class, 'store'])->middleware('auth:sanctum');

// api resource report

// /orders/all harus sebelum /orders/{date?}; dulu tertangkap route itu sehingga riwayat
// pesanan (dengan item) tidak pernah terpanggil.
Route::get('/orders/all', [OrderController::class, 'getAllOrder'])->middleware('auth:sanctum');
Route::get('/orders/{date?}', [OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('/summary/{date?}', [OrderController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/order-item/{date?}', [OrderItemController::class, 'index'])->middleware('auth:sanctum');
Route::get('/order-sales', [OrderItemController::class, 'orderSales'])->middleware('auth:sanctum');
Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->middleware('auth:sanctum');
Route::put('/orders/{id}/update', [OrderController::class, 'updateOrder'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('tables')->group(function () {
        Route::get('/', [TableManagementController::class, 'index']); // get all tables
        Route::post('/', [TableManagementController::class, 'store']); // create new table
        Route::put('/{id}', [TableManagementController::class, 'update']); // update table
        Route::patch('/{id}/position', [TableManagementController::class, 'changePosition']); // change table position
        Route::patch('/{id}/status', [TableManagementController::class, 'updateStatus']);
        Route::get('/status/{status}', [TableManagementController::class, 'getByStatus']); // get tables by status
        Route::delete('/{id}', [TableManagementController::class, 'destroy']); // delete table
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('draft-orders')->group(function () {
        Route::post('/', [DraftOrderController::class, 'store']);
        Route::get('/', [DraftOrderController::class, 'index']);
        Route::get('/{id}', [DraftOrderController::class, 'show']);
        Route::put('/{id}', [DraftOrderController::class, 'update']);
        Route::delete('/{id}', [DraftOrderController::class, 'destroy']);
    });
});

// tamu menginap untuk "tagih ke kamar" (data pribadi: wajib login)
Route::get('/reservation-guests', [HotelGuestController::class, 'index'])->middleware('auth:sanctum');
