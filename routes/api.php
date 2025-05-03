<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIController;
use App\Http\Controllers\StreamingController;
use App\Http\Controllers\QuizResultController;
use App\Http\Controllers\PromptConfigurationController;

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

// Quiz Result Routes
Route::get('/quiz-results', [QuizResultController::class, 'index']);
Route::get('/quiz-results/latest', [QuizResultController::class, 'latest']);
Route::get('/quiz-results/{id}', [QuizResultController::class, 'show']);
Route::post('/quiz-results', [QuizResultController::class, 'store']);

// AI Routes
Route::post('/insights', [AIController::class, 'insights']);
Route::post('/recommendations', [AIController::class, 'recommendations']);
Route::post('/generate', [AIController::class, 'generate']);

// Streaming Routes
Route::post('/stream/insights', [StreamingController::class, 'streamInsights']);
Route::post('/stream/recommendations', [StreamingController::class, 'streamRecommendations']);

// Prompt Configuration Routes
Route::get('/prompt-config', [PromptConfigurationController::class, 'index']);
Route::get('/prompt-config/{section}', [PromptConfigurationController::class, 'show']);
Route::post('/prompt-config', [PromptConfigurationController::class, 'store']);
Route::delete('/prompt-config/{section}', [PromptConfigurationController::class, 'destroy']);
Route::post('/prompt-config/{section}/reset', [PromptConfigurationController::class, 'reset']);
