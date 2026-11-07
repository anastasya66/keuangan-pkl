@echo off
setlocal enabledelayedexpansion
title FinToko - Instalasi dan Setup Otomatis

:: Pastikan selalu berada di folder project FinToko
cd /d "%~dp0"

echo ====================================================
echo        FinToko - Instalasi dan Setup Otomatis
echo ====================================================
echo.

:: 1. Deteksi path Laragon secara otomatis
set "LARAGON_DIR="
if exist "%~dp0..\..\bin\composer" set "LARAGON_DIR=%~dp0..\..\"
if not defined LARAGON_DIR if exist "E:\laragon\bin\composer" set "LARAGON_DIR=E:\laragon\"
if not defined LARAGON_DIR if exist "C:\laragon\bin\composer" set "LARAGON_DIR=C:\laragon\"
if not defined LARAGON_DIR if exist "D:\laragon\bin\composer" set "LARAGON_DIR=D:\laragon\"

if defined LARAGON_DIR (
    echo [INFO] Terdeteksi instalasi Laragon di: !LARAGON_DIR!
    set "PATH=!LARAGON_DIR!bin\composer;!PATH!"
    set "PATH=!LARAGON_DIR!bin\git\cmd;!PATH!"
    
    for /d %%P in ("!LARAGON_DIR!bin\php\php*") do (
        set "PHP_DIR=%%P"
    )
    if defined PHP_DIR (
        echo [INFO] Menggunakan PHP dari: !PHP_DIR!
        set "PATH=!PHP_DIR!;!PATH!"
    )
    
    for /d %%N in ("!LARAGON_DIR!bin\nodejs\node*") do (
        set "NODE_DIR=%%N"
    )
    if defined NODE_DIR (
        set "PATH=!NODE_DIR!;!PATH!"
    )
)

echo.
:: 2. Cek ketersediaan PHP
where php >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Program 'php' belum terdeteksi di sistem PATH.
    echo.
    echo SOLUSI:
    echo 1. Buka aplikasi Laragon.
    echo 2. Klik tombol "Terminal" di jendela utama Laragon.
    echo 3. Di dalam terminal Laragon, ketik: setup.bat
    echo.
    pause
    exit /b 1
)

:: 3. Cek ketersediaan Composer
where composer >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Program 'composer' belum terdeteksi.
    echo.
    echo SOLUSI:
    echo 1. Buka aplikasi Laragon.
    echo 2. Klik tombol "Terminal" di jendela utama Laragon.
    echo 3. Di dalam terminal Laragon, ketik: setup.bat
    echo.
    pause
    exit /b 1
)

:: Pindah ke folder script berada
cd /d "%~dp0"

echo [1/6] Mengunduh dependensi PHP (composer install)...
call composer install
if %ERRORLEVEL% neq 0 (
    echo.
    echo [PERINGATAN] Gagal menjalankan composer install.
    echo Pastikan koneksi internet aktif.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [2/6] Memeriksa file konfigurasi .env...
if not exist .env (
    echo Menyalin file .env.example ke .env...
    copy .env.example .env
) else (
    echo File .env sudah tersedia.
)

echo.
echo [3/6] Menghasilkan APP_KEY Laravel...
call php artisan key:generate --force

echo.
echo [4/6] Menjalankan migrasi dan data awal (seeder) database...
echo Pastikan MySQL atau MariaDB di Laragon sudah dalam keadaan START (aktif)!
call php artisan migrate --seed --force

echo.
echo [5/6] Mengunduh dependensi frontend (npm install)...
where npm >nul 2>nul
if %ERRORLEVEL% equ 0 (
    call npm install
    echo.
    echo [6/6] Melakukan kompilasi aset frontend (npm run build)...
    call npm run build
) else (
    echo [CATATAN] Node.js / npm belum terdeteksi, melewati langkah npm build.
)

echo.
echo ====================================================
echo        FinToko Berhasil Disetup dan Siap Digunakan!
echo.
echo   Cara menjalankan aplikasi:
echo   1. Buka Laragon, klik "Start All"
echo   2. Buka browser: http://localhost/keuangan-pkl/public
echo      atau jalankan perintah: php artisan serve
echo      lalu buka browser: http://127.0.0.1:8000
echo ====================================================
echo.
pause
