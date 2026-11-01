<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CompetitionController::class, 'home'])->name('home');

Route::get('/masuk', [AuthController::class, 'showParticipant'])->defaults('mode', 'login')->name('login');
Route::get('/daftar-akun', [AuthController::class, 'showParticipant'])->defaults('mode', 'signup')->name('signup');
Route::post('/masuk', [AuthController::class, 'loginParticipant'])->name('login.submit');
Route::post('/daftar-akun', [AuthController::class, 'registerParticipant'])->name('signup.submit');
Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');

Route::get('/lomba/{category}/daftar', [CompetitionController::class, 'registration'])->name('registration.show');
Route::post('/lomba/{category}/daftar', [CompetitionController::class, 'saveRegistration'])->name('registration.save');
Route::get('/lomba/{category}/unggah', [CompetitionController::class, 'work'])->name('work.show');
Route::post('/lomba/{category}/unggah', [CompetitionController::class, 'saveWork'])->name('work.save');
Route::get('/profil', [CompetitionController::class, 'profile'])->name('profile');
Route::post('/profil', [CompetitionController::class, 'updateProfile'])->name('profile.update');
Route::delete('/pendaftaran/{registration}', [CompetitionController::class, 'deleteRegistration'])->name('registration.delete');
Route::get('/notifikasi', [CompetitionController::class, 'notifications'])->name('notifications');
Route::post('/notifikasi/baca', [CompetitionController::class, 'markNotificationsRead'])->name('notifications.read');
Route::get('/berkas/{registration}/{type}', [CompetitionController::class, 'downloadFile'])->whereIn('type', ['pdf', 'word', 'kartu', 'pernyataan'])->name('files.download');
Route::get('/berkas-unduh-semua', [CompetitionController::class, 'downloadAll'])->name('files.download-all');

Route::get('/admin', fn () => session()->has('admin_id') ? redirect()->route('admin.dashboard') : redirect()->route('admin.login'))->name('admin');
Route::get('/admin/masuk', [AuthController::class, 'showAdmin'])->defaults('mode', 'login')->name('admin.login');
Route::get('/admin/daftar', [AuthController::class, 'showAdmin'])->defaults('mode', 'signup')->name('admin.signup');
Route::post('/admin/masuk', [AuthController::class, 'loginAdmin'])->name('admin.login.submit');
Route::post('/admin/daftar', [AuthController::class, 'registerAdmin'])->name('admin.signup.submit');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/tinjau/{registration}', [AdminController::class, 'review'])->name('review');
    Route::post('/tinjau/{registration}', [AdminController::class, 'updateReview'])->name('review.update');
    Route::get('/notifikasi', [AdminController::class, 'notifications'])->name('notifications');
    Route::get('/profil', [AdminController::class, 'profile'])->name('profile');
    Route::post('/profil', [AdminController::class, 'updateProfile'])->name('profile.update');
});
