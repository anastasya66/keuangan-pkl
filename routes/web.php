<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — FinToko Single Page Application
|--------------------------------------------------------------------------
|
| Semua route web diarahkan ke view 'app' (Blade) yang memuat bundle React.
| Route dengan awalan 'api/' dikecualikan agar ditangani oleh routes/api.php.
|
*/

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
