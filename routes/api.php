<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected authentication routes (require authentication)
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Public user routes (create user without authentication - for registration)
Route::prefix('users')->group(function () {
    Route::post('/', [UserController::class, 'store']);
});

// Protected user routes (require authentication)
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

// Role-based protected routes examples:
// Admin only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
});

// Chairman routes (chairman and admin can access)
Route::prefix('chairman')->middleware(['auth:sanctum', 'role:admin|chairman'])->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Chairman dashboard']);
    });
});

// Teacher routes (teacher, chairman, and admin can access)
Route::prefix('teachers')->middleware(['auth:sanctum', 'role:admin|chairman|teacher'])->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Teacher dashboard']);
    });
});

// Student routes (all authenticated users can access)
Route::prefix('students')->middleware(['auth:sanctum', 'role:admin|chairman|teacher|student'])->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Student dashboard']);
    });
});

