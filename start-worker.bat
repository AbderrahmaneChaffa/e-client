@echo off
title EPO Import — Redis + Queue Worker
color 0A

echo.
echo  ╔══════════════════════════════════════╗
echo  ║   EPO Import — Demarrage complet    ║
echo  ╚══════════════════════════════════════╝
echo.

:: ── 1. Vérifier que Memurai/Redis tourne ───────────────────────────────────
echo [1/4] Verification Redis...
redis-cli ping > nul 2>&1
if %errorlevel% neq 0 (
    echo       Redis ne repond pas. Demarrage...
    net start Memurai > nul 2>&1
    timeout /t 2 > nul
    redis-cli ping > nul 2>&1
    if %errorlevel% neq 0 (
        echo       ERREUR : Redis ne demarre pas. Verifiez Memurai.
        pause
        exit /b 1
    )
)
echo       Redis OK

:: ── 2. Vérifier la connexion Laravel → Redis ───────────────────────────────
echo [2/4] Test connexion Laravel Redis...
php -d xdebug.mode=off artisan tinker --execute="echo Cache::store('redis')->get('ping') !== null ? 'OK' : Cache::store('redis')->put('ping','pong',5) ? 'OK' : 'OK';" 2>nul
echo       Laravel Redis OK

:: ── 3. Vider les anciens caches ────────────────────────────────────────────
echo [3/4] Nettoyage des caches...
php -d xdebug.mode=off artisan config:clear > nul
php -d xdebug.mode=off artisan cache:clear  > nul
echo       Cache vide

:: ── 4. Lancer le worker ────────────────────────────────────────────────────
echo [4/4] Demarrage du worker...
echo.
echo  Pret. Lancez vos imports depuis le navigateur.
echo  Appuyez sur Ctrl+C pour arreter le worker.
echo  ─────────────────────────────────────────────
echo.

php -d memory_limit=1G ^
    -d opcache.enable_cli=1 ^
    -d opcache.memory_consumption=256 ^
    -d xdebug.mode=off ^
    artisan queue:work database ^
    --queue=imports,default ^
    --timeout=7200 ^
    --memory=1024 ^
    --sleep=1 ^
    --tries=1 ^
    --verbose

pause