<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SatusehatIdController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::middleware(['clinic.auth'])->group(function(){
    Route::post('/encounter', [\App\Http\Controllers\EncounterController::class, 'store']);
});

// Routes untuk testing SATUSEHAT service per klinik
Route::prefix('satusehat')->group(function () {
    Route::get('/token/{clinicCode}', [\App\Http\Controllers\EncounterController::class, 'getToken']);
    Route::post('/test-patient/{clinicCode}', [\App\Http\Controllers\EncounterController::class, 'testPatient']);
});

// Routes untuk mendapatkan dan mendaftarkan ID SATUSEHAT
Route::middleware(['clinic.auth'])->group(function(){
    Route::post('/satusehat/get-patient-id', [SatusehatIdController::class, 'getPatientId']);
    Route::post('/satusehat/get-practitioner-id', [SatusehatIdController::class, 'getPractitionerId']);
    Route::post('/satusehat/get-healthcare-service-id', [SatusehatIdController::class, 'getHealthcareServiceId']);
    Route::post('/satusehat/register-patient', [SatusehatIdController::class, 'registerPatient']);
    Route::post('/satusehat/register-practitioner', [SatusehatIdController::class, 'registerPractitioner']);
});