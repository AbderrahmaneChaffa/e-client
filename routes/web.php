<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth'])->group(function () {

    Route::middleware('role:Admin')->group(function () {
        Route::get('/admin/dashboard', fn () => view('admins.dashboard'))
            ->name('admin.dashboard');
    });

    Route::middleware('role:Client')->group(function () {
        Route::get('/client/dashboard', fn () => view('clients.dashboard'))
            ->name('client.dashboard');
    });

});

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
