<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SatusehatIdController;
use App\Http\Controllers\EncounterController;

/*
|--------------------------------------------------------------------------
| APIRoutes
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

// Routes for sending resources asynchronously (queued)
Route::middleware(['clinic.auth'])->group(function(){
    Route::post('/encounter', [\App\Http\Controllers\EncounterController::class, 'store']);
    Route::post('/send-patient-async', [EncounterController::class, 'sendPatientAsync']);
    Route::post('/send-practitioner-async', [EncounterController::class, 'sendPractitionerAsync']);
    Route::post('/send-encounter-async', [EncounterController::class, 'sendEncounterAsync']);
    Route::post('/send-observation-async', [EncounterController::class, 'sendObservationAsync']);
    Route::post('/send-procedure-async', [EncounterController::class, 'sendProcedureAsync']);
    Route::post('/send-condition-async', [EncounterController::class, 'sendConditionAsync']);
});

// Routes for testing SATUSEHAT service per clinic
Route::prefix('satusehat')->group(function () {
    Route::get('/token/{clinicCode}', [\App\Http\Controllers\EncounterController::class, 'getToken']);
    Route::post('/test-patient/{clinicCode}', [\App\Http\Controllers\EncounterController::class, 'testPatient']);
});

// Routes for getting and registering SATUSEHAT IDs
Route::middleware(['clinic.auth'])->group(function(){
    Route::post('/satusehat/get-patient-nik', [SatusehatIdController::class, 'getPatientByNIK']);
    Route::post('/satusehat/get-patient-id', [SatusehatIdController::class, 'getPatientByID']);
    Route::post('/satusehat/get-practitioner-id', [SatusehatIdController::class, 'getPractitionerId']);
    Route::post('/satusehat/get-practitioner-nik', [SatusehatIdController::class, 'getPractitionerNIK']);
    Route::post('/satusehat/get-healthcare-service-id', [SatusehatIdController::class, 'getHealthcareServiceId']);
    Route::post('/satusehat/register-patient', [SatusehatIdController::class, 'registerPatient']);
    Route::post('/satusehat/register-practitioner', [SatusehatIdController::class, 'registerPractitioner']);
    Route::post('/satusehat/send-location', [SatusehatIdController::class, 'sendLocation']);
    Route::post('/satusehat/send-encounter', [SatusehatIdController::class, 'sendEncounter']);
    Route::post('/satusehat/send-observation', [SatusehatIdController::class, 'sendObservation']);
    Route::post('/satusehat/send-procedure', [SatusehatIdController::class, 'sendProcedure']);
    Route::post('/satusehat/send-condition', [SatusehatIdController::class, 'sendCondition']);
    Route::post('/satusehat/send-bundle', [SatusehatIdController::class, 'sendBundleSatusehat']);
});
