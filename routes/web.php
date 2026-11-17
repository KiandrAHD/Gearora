<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public & customer routes
|--------------------------------------------------------------------------
|
| Phase 1 hanya menyediakan skeleton halaman. Modul katalog, panduan,
| autentikasi, dan akun pelanggan dihubungkan pada phase berikutnya.
|
*/

Route::view('/', 'home')->name('home');

Route::view('/katalog', 'catalog.index')->name('catalog.index');
Route::view('/panduan', 'guides.index')->name('guides.index');
Route::view('/akun', 'customer.dashboard')->name('customer.dashboard');