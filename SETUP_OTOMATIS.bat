@echo off
setlocal enabledelayedexpansion
title Pemasangan Otomatis Cerdas - ServiceHub - FokusKonten
color 0B
cls

echo ================================================================================
echo           FOKUSKONTEN OFFICIAL - ENGINE PEMASANGAN OTOMATIS CERDAS
echo   Aplikasi   : ServiceHub
echo   SKU        : ID93AF
echo   Distribusi : https://fokuskonten.my.id
echo ================================================================================
echo.

:: 1. DETEKSI LINGKUNGAN SERVER LOKAL DI SISTEM
set "SERVER_FOUND=0"
set "XAMPP_DIR="

if exist "C:\xampp\apache\bin\httpd.exe" (
    set "XAMPP_DIR=C:\xampp"
    set "SERVER_FOUND=1"
) else if exist "D:\xampp\apache\bin\httpd.exe" (
    set "XAMPP_DIR=D:\xampp"
    set "SERVER_FOUND=1"
) else if exist "E:\xampp\apache\bin\httpd.exe" (
    set "XAMPP_DIR=E:\xampp"
    set "SERVER_FOUND=1"
)

if "!SERVER_FOUND!"=="1" (
    echo [1/4] Mendeteksi lingkungan server lokal XAMPP... [DITEMUKAN: !XAMPP_DIR!]
    
    :: Memeriksa dan mengaktifkan Apache jika belum aktif
    echo       [*] Memeriksa status Web Server Apache...
    netstat -ano | findstr LISTENING | findstr /R /C:":80 " >nul
    if errorlevel 1 (
        echo       [*] Menyalakan layanan Apache di latar belakang...
        pushd "!XAMPP_DIR!"
        start "" /min apache\bin\httpd.exe
        popd
    )
    
    :: Memeriksa dan mengaktifkan MySQL jika belum aktif
    echo       [*] Memeriksa status Basis Data MySQL...
    netstat -ano | findstr LISTENING | findstr /R /C:":3306 " >nul
    if errorlevel 1 (
        echo       [*] Menyalakan modul MySQL di latar belakang...
        pushd "!XAMPP_DIR!"
        start "" /min mysql\bin\mysqld.exe --defaults-file=mysql\bin\my.ini --standalone
        popd
    )
    
    :: Healthcheck Barrier: Menunggu server siap 100%
    echo       [*] Menunggu stabilisasi layanan server...
    for /l %%i in (1,1,6) do (
        ping 127.0.0.1 -n 2 >nul
        netstat -ano | findstr LISTENING | findstr /R /C:":80 " >nul
        if not errorlevel 1 goto server_ready
    )
) else (
    echo [1/4] Mendeteksi server lokal... [BELUM DITEMUKAN]
    echo.
    echo ================================================================================
    echo   [INFO] APLIKASI WEB INI MEMERLUKAN RUNTIME SERVER LOKAL (XAMPP GRATIS)
    echo ================================================================================
    echo.
    echo   Sistem kami mendeteksi XAMPP belum terpasang di komputer Anda.
    echo.
    echo   LANGKAH MUDAH 1 KALI PEMASANGAN:
    echo   1. Tekan tombol [1] lalu ENTER untuk otomatis mengunduh installer XAMPP resmi.
    echo   2. Pasang (Install) di folder default C:\xampp
    echo   3. Setelah selesai, klik kembali SETUP_OTOMATIS.bat ini.
    echo.
    echo   Bantuan Gratis via WhatsApp Tim FokusKonten: +62 851-8301-1318
    echo ================================================================================
    echo.
    set /p usrchoice="Pilihan Anda (Ketik 1 untuk unduh XAMPP, atau 2 untuk Bantuan WA): "
    if "!usrchoice!"=="1" (
        echo Membuka halaman unduhan resmi XAMPP di peramban Anda...
        start https://www.apachefriends.org/download.html
    ) else if "!usrchoice!"=="2" (
        start https://wa.me/6285183011318?text=Halo%%20Admin%%20FokusKonten,%%20saya%%20butuh%%20bantuan%%20pemasangan%%20software%%20ServiceHub
    )
    exit /b
)

:server_ready
echo       [*] Server lokal aktif dan siap melayani permintaan! [SUKSES]
echo.

:: 2. PENYIAPAN DIRECTORY & SOURCE CODE HTDOCS
echo [2/4] Menyiapkan direktori aplikasi ke server web lokal...
set "APP_SLUG=servicehub"
set "TARGET_DIR=!XAMPP_DIR!\htdocs\!APP_SLUG!"

if not exist "!TARGET_DIR!" mkdir "!TARGET_DIR!" >nul 2>&1

if exist "%~dp0SOURCE_CODE_APLIKASI" (
    robocopy "%~dp0SOURCE_CODE_APLIKASI" "!TARGET_DIR!" /E /NFL /NDL /NJH /NJS /nc /ns >nul 2>&1
)
echo       [*] Berkas aplikasi terpasang di: !TARGET_DIR! [SUKSES]
echo.

:: 3. AUTO-PROVISIONING DATABASE SQL
echo [3/4] Mengonfigurasi basis data dan akun pengguna...
if exist "!XAMPP_DIR!\mysql\bin\mysql.exe" (
    "!XAMPP_DIR!\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS service_repair_db;" 2>nul
    if exist "%~dp020250923195005_noisy_morning.sql" (
        "!XAMPP_DIR!\mysql\bin\mysql.exe" -u root service_repair_db < "%~dp020250923195005_noisy_morning.sql" 2>nul
    )
    echo       [*] Basis data service_repair_db berhasil disinkronkan. [SUKSES]
)
echo.

:: 4. MELUNCURKAN APLIKASI DALAM DESKTOP APP MODE
echo [4/4] Meluncurkan ServiceHub dalam Desktop App PWA Mode...
echo ================================================================================
echo   APLIKASI TELAH SIAP! Jendela Desktop App akan segera terbuka di layar Anda.
echo ================================================================================
ping 127.0.0.1 -n 2 >nul

set "TARGET_URL=http://localhost/servicehub/"

if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
    start "" "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" --app="!TARGET_URL!" --window-size=1280,800
    exit
)
if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
    start "" "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" --app="!TARGET_URL!" --window-size=1280,800
    exit
)
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" (
    start "" "%ProgramFiles%\Google\Chrome\Application\chrome.exe" --app="!TARGET_URL!" --window-size=1280,800
    exit
)

start !TARGET_URL!
exit
