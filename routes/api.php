<?php

use App\Http\Controllers\Api\AdminCutiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CutiController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::prefix('auth')->group(function () {
  Route::post('register', [AuthController::class, 'register']);
  Route::post('login', [AuthController::class, 'login']);

  // OAuth Google
  Route::get('google', [AuthController::class, 'redirectToGoogle']);
  Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
});


Route::middleware('auth:sanctum')->group(function () {

  Route::post('auth/logout', [AuthController::class, 'logout']);
  Route::get('auth/me', [AuthController::class, 'me']);

  // Karyawan
  Route::middleware('role:karyawan')->prefix('cuti')->group(function () {
    Route::get('kuota', [CutiController::class, 'kuota']);
    Route::get('/', [CutiController::class, 'index']);
    Route::post('/', [CutiController::class, 'store']);
    Route::get('{cuti}', [CutiController::class, 'show']);
  });

  // Admin
  Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('cuti', [AdminCutiController::class, 'index']);
    Route::get('cuti/{cuti}', [AdminCutiController::class, 'show']);
    Route::patch('cuti/{cuti}/review', [AdminCutiController::class, 'review']);
    Route::get('karyawan', [AdminCutiController::class, 'karyawan']);
  });
});
