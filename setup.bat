@echo off
title FinToko Setup
echo ====================================================
echo        FinToko - Instalasi & Setup Otomatis
echo ====================================================
echo.

echo [1/6] Mengunduh dependensi PHP (Composer)...
call composer install
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Gagal menjalankan composer install. Pastikan Composer sudah terpasang.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [2/6] Memeriksa file konfigurasi .env...
if not exist .env (
    echo Menyalin .env.example ke .env...
    copy .env.example .env
)

echo.
echo [3/6] Menghasilkan APP_KEY Laravel...
call php artisan key:generate

echo.
echo [4/6] Menjalankan migrasi dan seeder database MariaDB...
echo Pastikan MySQL / MariaDB di Laragon sudah dalam keadaan START (aktif)!
call php artisan migrate --seed --force

echo.
echo [5/6] Mengunduh dependensi frontend (npm install)...
call npm install

echo.
echo [6/6] Melakukan kompilasi aset frontend (npm run build)...
call npm run build

echo.
echo ====================================================
echo   FinToko Berhasil Disetup dan Siap Digunakan!
echo   Buka di peramban: http://localhost/keuangan-pkl/public
echo   atau jalankan: php artisan serve
echo ====================================================
echo.
pause

