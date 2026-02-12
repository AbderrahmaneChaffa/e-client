<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});
Route::middleware(['auth', 'verified'])->group(function () {

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');
        Route::get('/factures', [FactureController::class, 'index'])->name('admin.factures.index');
        Route::get('/paiements', [PaiementController::class, 'index'])->name('admin.paiements.index');
        Route::get('/factures/{facture}', [FactureController::class, 'show'])->name('admin.factures.show');
        // MODULE IMPORTATION EXCEL
        Route::get('/imports', [ImportController::class, 'index'])->name('admin.imports.index');
        //Route::post('/imports', [ImportController::class, 'store'])->name('admin.imports.store');
        Route::post('/imports/upload-temp', [ImportController::class, 'uploadTemp'])->name('admin.imports.upload-temp');
        Route::post('/imports/analyze', [ImportController::class, 'analyze'])->name('admin.imports.analyze');
        Route::post('/imports/store', [ImportController::class, 'store'])->name('admin.imports.store');
        // GESTION DES CLIENTS
        Route::resource('clients', ClientController::class, ['as' => 'admin']);
    });

    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', fn() => view('clients.dashboard'))
            ->name('client.dashboard');
    });
});



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/generate-test-excel', function () {
    $handle = fopen('test_massif_epo2.csv', 'w');
    fputcsv($handle, ['n_facture', 'date', 'client_code', 'client_nom', 'nis', 't_ht', 't_tva', 't_ttc']);

    for ($i = 1; $i <= 30000; $i++) {
        fputcsv($handle, [
            "FAC-TEST-$i",
            "2026-02-11",
            "CLT-" . rand(1, 100),
            "Client Test " . rand(1, 100),
            "1234567890",
            1000,
            190,
            1190
        ]);
    }
    fclose($handle);
    return "Fichier test_massif_epo2.csv généré à la racine !";
});
require __DIR__ . '/auth.php';
