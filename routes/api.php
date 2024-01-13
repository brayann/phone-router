<?php

use App\Http\Controllers\TwilioController;
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

Route::post('/twilio/inbound', [TwilioController::class, 'inbound'])->name('twilio.inbound');
Route::post('/twilio/voicemail', [TwilioController::class, 'voicemail'])->name('twilio.voicemail');
