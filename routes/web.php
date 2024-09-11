<?php

use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard', function () {
    return view('dashboard',[
        'listings' => request()->user()->listings
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('v1')->group(function () {
    Route::group([
        'controller' => ListingController::class,
        'as'         => 'listings.',
    ], function(){
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/{listing}', 'show')->name('show');
        Route::get('/{listing}/apply', 'apply')->name('apply');
        Route::post('/store', 'store')->name('store');
    });
});

require __DIR__.'/auth.php';
