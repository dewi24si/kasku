<?php

use App\Http\Controllers\TransactionExportController;
use App\Livewire\Budget;
use App\Livewire\Chat;
use App\Livewire\Dashboard;
use App\Livewire\Profile;
use App\Livewire\TransactionHistory;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::livewire('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

Route::livewire('/chat', Chat::class)
    ->middleware('auth')
    ->name('chat');

Route::livewire('/budget', Budget::class)
    ->middleware('auth')
    ->name('budget');

Route::livewire('/profil', Profile::class)
    ->middleware('auth')
    ->name('profil');

Route::livewire('/riwayat', TransactionHistory::class)
    ->middleware('auth')
    ->name('riwayat');

Route::middleware('auth')->controller(TransactionExportController::class)->group(function (): void {
    Route::get('/riwayat/ekspor', 'csv')->name('riwayat.ekspor');
    Route::get('/riwayat/ekspor-pdf', 'pdf')->name('riwayat.ekspor-pdf');
});

require __DIR__.'/auth.php';
