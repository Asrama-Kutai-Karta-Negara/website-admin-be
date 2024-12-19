<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Middleware\HeaderMiddleware;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1/auth',
    'middleware' => [HeaderMiddleware::class],
], function ($router) {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});


Route::middleware([JwtMiddleware::class, HeaderMiddleware::class])->group(function () {
    Route::prefix('v1')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('residents', [ResidentController::class, 'index']);
        Route::post('residents', [ResidentController::class, 'store']);
        Route::get('residents/{id}', [ResidentController::class, 'show']);
        Route::put('residents/{id}', [ResidentController::class, 'update']);
        Route::delete('residents/{id}', [ResidentController::class, 'destroy']);

        Route::get('galleries', [GalleryController::class, 'index']);
        Route::post('galleries', [GalleryController::class, 'store']);
        Route::get('galleries/{id}', [GalleryController::class, 'show']);
        Route::get('galleries/get-file/{id}', [GalleryController::class, 'showFile']);
        Route::put('galleries/{id}', [GalleryController::class, 'update']);
        Route::delete('galleries/{id}', [GalleryController::class, 'destroy']);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('payments/{id}', [PaymentController::class, 'show']);
        Route::put('payments/{id}', [PaymentController::class, 'update']);
        Route::delete('payments/{id}', [PaymentController::class, 'destroy']);

        Route::get('reports', [FinancialReportController::class, 'index']);
        Route::post('reports', [FinancialReportController::class, 'store']);
        Route::get('reports/{id}', [FinancialReportController::class, 'show']);
        Route::put('reports/{id}', [FinancialReportController::class, 'update']);
        Route::put('reports-sync', [FinancialReportController::class, 'syncPayment']);
        Route::delete('reports/{id}', [FinancialReportController::class, 'destroy']);
    });
});

Route::middleware([HeaderMiddleware::class])->group(function () {
    Route::prefix('v1/public')->group(function () {
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('galleries', [GalleryController::class, 'index']);
    });
});
