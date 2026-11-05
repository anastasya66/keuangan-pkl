<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — FinToko Single Page Application & Direct API Aliases
|--------------------------------------------------------------------------
|
| Mendukung akses API langsung via /api/* maupun /api/v1/*, serta catch-all
| SPA route ke Blade view 'app' untuk bundle React.
|
*/

// Alias route untuk melayani permintaan /api/* secara langsung (tanpa prefix /v1)
Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));

// Catch-all route untuk React Single Page Application (kecualikan /api/*)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
