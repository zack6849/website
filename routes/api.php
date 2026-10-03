<?php
declare(strict_types=1);

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

use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\Logbook\LogbookController;
use App\Http\Controllers\TwilioLookup\TwilioController;

Route::middleware("auth:sanctum")->group(callback: function () {
    Route::prefix("/files")->controller(FileController::class)->group(function () {
        Route::post('/', 'store')->name("api.file.store");
        Route::delete('/{file:filename}', 'destroy')->name("api.file.delete");
    });
    Route::prefix('/twilio')->controller(TwilioController::class)->group(function () {
        Route::post('/lookup', 'twilioResponse')->name('twilio.sms');
    });
});
Route::prefix('radio')->controller(LogbookController::class)->group(function(){
    Route::get('qsos/band/{band?}/mode/{mode?}', 'getGeoJSON');
    Route::get('modes', 'getWorkedModes');
    Route::get('bands', 'getWorkedBands');
});
