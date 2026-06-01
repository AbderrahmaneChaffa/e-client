@echo off
setlocal EnableExtensions EnableDelayedExpansion

echo E-Client EPO - preparation de l'environnement local

where php >nul 2>nul
if errorlevel 1 (
    echo Erreur : php est introuvable. Installez PHP puis relancez ce script.
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo Erreur : composer est introuvable. Installez Composer puis relancez ce script.
    exit /b 1
)

where node >nul 2>nul
if errorlevel 1 (
    echo Erreur : node est introuvable. Installez Node.js puis relancez ce script.
    exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
    echo Erreur : npm est introuvable. Installez npm puis relancez ce script.
    exit /b 1
)

if not exist vendor\autoload.php (
    echo Dependances Composer absentes. Installation...
    composer install --no-interaction
    if errorlevel 1 exit /b 1
)

if not exist node_modules (
    echo Dependances NPM absentes. Installation...
    npm install
    if errorlevel 1 exit /b 1
)

echo Nettoyage des caches Laravel...
php artisan config:clear
if errorlevel 1 exit /b 1
php artisan route:clear
if errorlevel 1 exit /b 1
php artisan view:clear
if errorlevel 1 exit /b 1

set ASSET_COMMAND=npm run dev
set PROCESS_NAMES=serve,vite,queue
set PROCESS_COLORS=blue,magenta,yellow

if /I "%NODE_ENV%"=="production" (
    set ASSET_COMMAND=npm run build
    set PROCESS_NAMES=serve,build,queue
    set PROCESS_COLORS=blue,green,yellow
)

echo E-Client EPO demarre. Serveur: http://127.0.0.1:8000 ^| Queue: imports,default ^| Vite: hot reload
echo Appuyez sur Ctrl+C pour arreter tous les processus.

npx concurrently --kill-others-on-fail --names "%PROCESS_NAMES%" --prefix "[{name}]" --prefix-colors "%PROCESS_COLORS%" "php artisan serve --port=8000" "%ASSET_COMMAND%" "php artisan queue:work database --queue=imports,default --timeout=7200 --memory=1024 --tries=1 --sleep=1"

endlocal
