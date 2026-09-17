<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LmsController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LosController;
use App\Http\Controllers\RecoveryController;
use Illuminate\Support\Facades\Route;

// ── Authentication (Public) ───────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── All Authenticated Routes ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Dashboard — All Roles ─────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── LMS: Loan Management System — All Roles ───────────────────────────
    Route::prefix('lms')->name('lms.')->group(function () {
        Route::get('/cds', [LmsController::class, 'cds'])->name('cds');
        Route::get('/loans/{id}', [LoanController::class, 'show'])->name('loans.show');
        Route::post('/loans/{id}/collect', [LoanController::class, 'collect'])->name('loans.collect');
    });

    // ── LOS: Loan Origination — Agent Routes ──────────────────────────────
    Route::prefix('los')->name('los.')->middleware('role:agent,admin')->group(function () {
        Route::get('/apply', [LosController::class, 'apply'])->name('apply');
        Route::post('/apply', [LosController::class, 'store'])->name('store');
        Route::get('/my-applications', [LosController::class, 'myApplications'])->name('my-applications');
        Route::get('/center', [LosController::class, 'center'])->name('center');
        Route::post('/center', [LosController::class, 'storeCenter'])->name('center.store');
        Route::put('/center/{id}', [LosController::class, 'updateCenter'])->name('center.update');
        Route::delete('/center/{id}', [LosController::class, 'destroyCenter'])->name('center.destroy');
        Route::get('/group', [LosController::class, 'group'])->name('group');
        Route::post('/group', [LosController::class, 'storeGroup'])->name('group.store');
        Route::put('/group/{id}', [LosController::class, 'updateGroup'])->name('group.update');
        Route::delete('/group/{id}', [LosController::class, 'destroyGroup'])->name('group.destroy');
        Route::get('/member', [LosController::class, 'member'])->name('member');
        Route::post('/member', [LosController::class, 'storeMember'])->name('member.store');
        Route::put('/member/{id}', [LosController::class, 'updateMember'])->name('member.update');
        Route::delete('/member/{id}', [LosController::class, 'destroyMember'])->name('member.destroy');
    });

    // ── LOS: Loan Origination — Manager Routes ───────────────────────────
    Route::prefix('los')->name('los.')->middleware('role:manager,admin')->group(function () {
        Route::get('/pipeline', [LosController::class, 'pipeline'])->name('pipeline');
        Route::get('/applications/{id}/review', [LosController::class, 'review'])->name('review');
        Route::post('/applications/{id}/approve', [LosController::class, 'approve'])->name('approve');
        Route::post('/applications/{id}/reject', [LosController::class, 'reject'])->name('reject');
        Route::post('/documents/{id}/verify', [LosController::class, 'verifyDocument'])->name('verify-document');
    });

    // ── Recovery / DRMS — All Authenticated (controller handles role filtering) ──
    Route::prefix('recovery')->name('recovery.')->group(function () {
        Route::get('/console', [RecoveryController::class, 'console'])->name('console');
        Route::post('/log-contact', [RecoveryController::class, 'logContact'])->name('log-contact');
        Route::post('/assign-agent', [RecoveryController::class, 'assignAgent'])
            ->name('assign-agent')
            ->middleware('role:manager,admin');

        Route::get('/legal', [LegalController::class, 'index'])->name('legal');
        Route::post('/legal/ots', [LegalController::class, 'otsCalculate'])->name('legal.ots');
        Route::get('/legal/notice/{id}', [LegalController::class, 'noticePreview'])->name('legal.notice');
    });

    // ── Admin — Admin Only ────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users', [AdminController::class, 'createUser'])->name('users.create');
        Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.destroy');
        Route::get('/config', [AdminController::class, 'config'])->name('config');
    });
});
