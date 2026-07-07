<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'dashboard.index')
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::livewire('users', 'users.index')
        ->middleware('permission:users.view')
        ->name('users.index');
    Route::livewire('users/create', 'users.create')
        ->middleware('permission:users.create')
        ->name('users.create');
    Route::livewire('users/{user}/edit', 'users.edit')
        ->middleware('permission:users.edit')
        ->name('users.edit');

    Route::livewire('roles', 'roles.index')
        ->middleware('permission:role.view')
        ->name('roles.index');
    Route::livewire('roles/create', 'roles.create')
        ->middleware('permission:role.create')
        ->name('roles.create');
    Route::livewire('roles/{role}/edit', 'roles.edit')
        ->middleware('permission:role.edit')
        ->name('roles.edit');

    Route::livewire('permissions', 'permissions.index')
        ->middleware('permission:permission.view')
        ->name('permissions.index');
    Route::livewire('permissions/create', 'permissions.create')
        ->middleware('permission:permission.create')
        ->name('permissions.create');
    Route::livewire('permissions/{permission}/edit', 'permissions.edit')
        ->middleware('permission:permission.edit')
        ->name('permissions.edit');

    Route::livewire('pengaduan', 'pengaduan.index')
        ->middleware('permission:pengaduan.view')
        ->name('pengaduan.index');
    Route::livewire('pengaduan/create', 'pengaduan.create')
        ->middleware('permission:pengaduan.create')
        ->name('pengaduan.create');
    Route::livewire('pengaduan/{pengaduan}', 'pengaduan.show')
        ->middleware('permission:pengaduan.view')
        ->name('pengaduan.show');
    Route::livewire('pengaduan/{pengaduan}/edit', 'pengaduan.edit')
        ->middleware('permission:pengaduan.edit')
        ->name('pengaduan.edit');

    Route::redirect('informasi-kegiatan', 'program');
    Route::redirect('informasi-kegiatan/create', 'program/create');
    Route::redirect('informasi-kegiatan/{programBanjar}/edit', 'program/{programBanjar}/edit');

    Route::livewire('program', 'program.index')
        ->middleware('permission:program.view')
        ->name('program.index');
    Route::livewire('program/create', 'program.create')
        ->middleware('permission:program.create')
        ->name('program.create');
    Route::livewire('program/{programBanjar}/edit', 'program.edit')
        ->middleware('permission:program.edit')
        ->name('program.edit');

    Route::livewire('dokumentasi', 'dokumentasi.index')
        ->middleware('permission:dokumentasi.view')
        ->name('dokumentasi.index');
    Route::livewire('dokumentasi/create', 'dokumentasi.create')
        ->middleware('permission:dokumentasi.create')
        ->name('dokumentasi.create');
    Route::livewire('dokumentasi/{dokumentasiKegiatan}/edit', 'dokumentasi.edit')
        ->middleware('permission:dokumentasi.edit')
        ->name('dokumentasi.edit');
});

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
