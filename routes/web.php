<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\FileUploadController;
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
        Route::get('/factures/{facture}', [FactureController::class, 'show'])->name('admin.factures.show');
        Route::get('/factures/{facture}/print', [FactureController::class, 'print'])->name('admin.factures.print');
        // MODULE IMPORTATION EXCEL
        Route::get('/imports', [ImportController::class, 'index'])->name('admin.imports.index');
        // import factures and paiements separately
        Route::get('/paiements', [PaiementController::class, 'index'])->name('admin.paiements.index');

        Route::get('/imports/template/factures', [ImportController::class, 'templateFactures'])->name('admin.imports.template.factures');
        Route::post('/imports/factures', [ImportController::class, 'storeFactures'])->name('admin.imports.factures');
        Route::get('/imports/template/paiements', [ImportController::class, 'templatePaiements'])->name('admin.imports.template.paiements');
        Route::post('/imports/paiements', [ImportController::class, 'storePaiements'])->name('admin.imports.paiements');
        // legacy/temp endpoints left for compatibility
        Route::post('/imports/upload-temp', [ImportController::class, 'uploadTemp'])->name('admin.imports.upload-temp');
        Route::post('/imports/store', [ImportController::class, 'storeFactures']); // alias to factures

        // GESTION DES CLIENTS
        Route::resource('clients', ClientController::class, ['as' => 'admin']);
    });

    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', [\App\Http\Controllers\Client\DashboardController::class, 'index'])
            ->name('client.dashboard');

        // Client invoices and payments
        Route::get('/client/factures', [\App\Http\Controllers\Client\FactureController::class, 'index'])
            ->name('client.factures.index');
        Route::get('/client/factures/{facture}', [\App\Http\Controllers\Client\FactureController::class, 'show'])
            ->name('client.factures.show');

        Route::get('/client/paiements', [\App\Http\Controllers\Client\PaiementController::class, 'index'])
            ->name('client.paiements.index');
    });
});
Route::get('/upload', [FileUploadController::class, 'showForm'])->name('upload.form');
Route::post('/upload', [FileUploadController::class, 'upload'])->name('file.upload');


// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

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
