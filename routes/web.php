<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\FactureController as ClientFactureController;
use App\Http\Controllers\Client\PaiementController as ClientPaiementController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProfileController;
use App\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return Auth::user()->role === UserRole::ADMIN
        ? redirect()->route('admin.dashboard')
        : redirect()->route('client.dashboard');
});

Route::get('/dashboard', function () {
    return Auth::user()->role === UserRole::ADMIN
        ? redirect()->route('admin.dashboard')
        : redirect()->route('client.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])
        ->name('notifications.feed');
    Route::get('/notifications/{notification}', [NotificationController::class, 'open'])
        ->name('notifications.open');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');

        Route::get('/admin/factures', [FactureController::class, 'index'])
            ->name('admin.factures.index');
        Route::get('/admin/factures/{facture}', [FactureController::class, 'show'])
            ->name('admin.factures.show');
        Route::get('/admin/factures/{facture}/print', [FactureController::class, 'print'])
            ->name('admin.factures.print');
        Route::get('/admin/paiements', [PaiementController::class, 'index'])
            ->name('admin.paiements.index');

        Route::prefix('/admin/imports')->name('admin.imports.')->group(function () {
            Route::get('/', [ImportController::class, 'index'])->name('index');
            Route::post('/', [ImportController::class, 'store'])->name('store');
            Route::post('/preview', [ImportController::class, 'preview'])->name('preview');
            Route::get('/progress', [ImportController::class, 'progressMany'])->name('progress-many');
            Route::post('/verify-global', [ImportController::class, 'verifyGlobal'])->name('verify-global');
            Route::get('/verify-global/status', [ImportController::class, 'verifyGlobalStatus'])->name('verify-global.status');
            Route::get('/{batch}', [ImportController::class, 'show'])->name('show');
            Route::get('/{batch}/progress', [ImportController::class, 'progress'])->name('progress');
            Route::post('/{batch}/resume', [ImportController::class, 'resume'])->name('resume');
            Route::delete('/{batch}', [ImportController::class, 'destroy'])->name('destroy');
        });

        Route::get('/admin/imports/template/factures', [ImportController::class, 'templateFactures'])
            ->name('admin.imports.template.factures');
        Route::post('/admin/imports/factures', [ImportController::class, 'storeFactures'])
            ->name('admin.imports.factures');
        Route::get('/admin/imports/template/paiements', [ImportController::class, 'templatePaiements'])
            ->name('admin.imports.template.paiements');
        Route::post('/admin/imports/paiements', [ImportController::class, 'storePaiements'])
            ->name('admin.imports.paiements');
        Route::post('/admin/imports/upload-temp', [ImportController::class, 'uploadTemp'])
            ->name('admin.imports.upload-temp');
        Route::post('/admin/imports/store', [ImportController::class, 'storeFactures']);

        Route::resource('/admin/clients', ClientController::class, ['as' => 'admin']);
    });

    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', [ClientDashboardController::class, 'index'])
            ->name('client.dashboard');

        Route::get('/client/factures', [ClientFactureController::class, 'index'])
            ->name('client.factures.index');
        Route::get('/client/factures/export/excel', [ClientFactureController::class, 'exportExcel'])
            ->name('client.factures.export.excel');
        Route::get('/client/factures/export/pdf', [ClientFactureController::class, 'exportPdf'])
            ->name('client.factures.export.pdf');
        Route::get('/client/factures/{facture}', [ClientFactureController::class, 'show'])
            ->name('client.factures.show');
        Route::get('/factures/{facture}/print', [ClientFactureController::class, 'print'])
            ->name('client.invoices.facture.print');

        Route::get('/client/paiements', [ClientPaiementController::class, 'index'])
            ->name('client.paiements.index');
        Route::get('/client/paiements/export/excel', [ClientPaiementController::class, 'exportExcel'])
            ->name('client.paiements.export.excel');
        Route::get('/client/paiements/export/pdf', [ClientPaiementController::class, 'exportPdf'])
            ->name('client.paiements.export.pdf');
        Route::get('/client/paiements/print', [ClientPaiementController::class, 'print'])
            ->name('client.paiements.print');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
