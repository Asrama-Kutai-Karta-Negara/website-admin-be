<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\OriginCampusController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RoomNumberController;
use App\Http\Controllers\Api\OriginCityController;
use App\Http\Controllers\Api\StaticController;
use App\Http\Middleware\HeaderMiddleware;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1/auth',
    'middleware' => [HeaderMiddleware::class],
], function ($router) {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('check-token', [AuthController::class, 'checkToken']);
    Route::post('refresh-token', [AuthController::class, 'refreshToken']);
});


Route::middleware([JwtMiddleware::class, HeaderMiddleware::class])->group(function () {
    Route::prefix('v1')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('residents', [ResidentController::class, 'index']);
        Route::get('get-residents-index', [ResidentController::class, 'getIndex']);
        Route::post('residents', [ResidentController::class, 'store']);
        Route::get('residents/{id}', [ResidentController::class, 'show']);
        Route::put('residents/{id}', [ResidentController::class, 'update']);
        Route::delete('residents/{id}', [ResidentController::class, 'destroy']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('room-numbers', [RoomNumberController::class, 'index']);
        Route::get('origin-campuses', [OriginCampusController::class, 'index']);
        Route::get('origin-cities', [OriginCityController::class, 'index']);

        Route::get('galleries', [GalleryController::class, 'index']);
        Route::post('galleries', [GalleryController::class, 'store']);
        Route::get('galleries/{id}', [GalleryController::class, 'show']);
        Route::get('galleries/get-file/{id}', [GalleryController::class, 'showFile']);
        Route::put('galleries/{id}', [GalleryController::class, 'update']);
        Route::delete('galleries/{id}', [GalleryController::class, 'destroy']);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('payments/{id}', [PaymentController::class, 'show']);
        Route::get('payments/get-file/{id}', [PaymentController::class, 'showFile']);
        Route::put('payments/{id}', [PaymentController::class, 'update']);
        Route::delete('payments/{id}', [PaymentController::class, 'destroy']);

        Route::get('reports', [FinancialReportController::class, 'index']);
        Route::post('reports', [FinancialReportController::class, 'store']);
        Route::post('reports/export', [FinancialReportController::class, 'exportReport']);
        Route::get('reports/{id}', [FinancialReportController::class, 'show']);
        Route::get('reports/get-file/{id}', [FinancialReportController::class, 'showFile']);
        Route::put('reports/{id}', [FinancialReportController::class, 'update']);
        Route::put('reports-sync', [FinancialReportController::class, 'syncPayment']);
        Route::delete('reports/{id}', [FinancialReportController::class, 'destroy']);
        Route::get('reports/generate/get-file/{filename}', [FinancialReportController::class, 'showFileReport']);

        Route::get('residents/grafik/active', [StaticController::class, 'getResidentActive']);
        Route::get('rooms/grafik/occupied', [StaticController::class, 'getOccupiedRoom']);
        Route::get('payments/grafik/sync', [StaticController::class, 'getSinkronisasiPayment']);
        Route::get('income/grafik/{bulan}', [StaticController::class, 'getPemasukan']);
        Route::get('outcome/grafik/{bulan}', [StaticController::class, 'getPengeluran']);
    });
});

Route::middleware([HeaderMiddleware::class])->group(function () {
    Route::prefix('v1/public')->group(function () {
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('galleries', [GalleryController::class, 'index']);
    });
});
