<?php
declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin\AdminBackgroundController;
use App\Http\Controllers\Admin\AdminLogbookController;
use App\Http\Controllers\Files\FileController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TwilioLookup\TwilioController;

Route::controller(PageController::class)->group(function() {
    Route::get('/', 'home')->name("home");
    Route::get('/photos', 'photos')->name('photos');
    Route::get('/radio', 'radio')->name('radio');
});

//disable registration
Auth::routes(['register' => false]);

//authentication required routes
Route::middleware('auth')->group(function() {
    Route::controller(AccountController::class)->prefix('/account')->middleware(['auth'])->group(function() {
        Route::get('/', 'settings');
    });

    //admin-only routes
    Route::prefix('/admin')->middleware(['admin'])->group(function () {
        Route::controller(AdminLogbookController::class)->prefix('/logbook')->group(function () {
            Route::get('/', 'index')->name('admin.logbook.index');
        });

        Route::controller(AdminBackgroundController::class)->prefix('/backgrounds')->group(function () {
            Route::get('/', 'index')->name('admin.backgrounds.index');
            Route::get('/create', 'create')->name('admin.backgrounds.create');
            Route::get('/{background}/edit', 'edit')->name('admin.backgrounds.edit');
        });
    });
});




Route::prefix('/files')->controller(FileController::class)->group(function () {
    //protected file routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/', 'index')->name("file.index");
        Route::get('/new', 'create')->name("file.create");
        Route::put('/', 'store')->name('file.store');
    });
    //filename specific routes
    Route::prefix('/{file:filename}/')->group(function () {
        //public route
        Route::get('/', 'show')->name('file.show');
        //protected filename specific routes
        Route::middleware(['auth'])->group(function () {
            Route::get('/delete', 'delete')->name('file.delete');
            Route::post('/delete', 'destroy')->name("file.destroy");
        });
    });

});

Route::prefix('/lookup')->controller(TwilioController::class)->group(function() {
    Route::get('/', 'index')->name("phone.lookup.index");
    Route::get('/{phone_number}', 'lookup')->name("phone.lookup");
    Route::get('/{phone_number}/raw', 'rawLookup')->name("phone.lookup.raw");
});

//Redirects for files to new URl format
Route::get('/uploads/{file:filename}', function(\App\Models\File $file){
    return redirect($file->url, 301);
})->name('file.old.redirect');
