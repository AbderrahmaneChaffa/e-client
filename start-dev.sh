#!/usr/bin/env bash

set -euo pipefail

echo "E-Client EPO - préparation de l'environnement local"

for command_name in php composer node npm; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Erreur : '$command_name' est introuvable. Installez-le puis relancez ce script."
        exit 1
    fi
done

if [ ! -f "vendor/autoload.php" ]; then
    echo "Dépendances Composer absentes. Installation..."
    composer install --no-interaction
fi

if [ ! -d "node_modules" ]; then
    echo "Dépendances NPM absentes. Installation..."
    npm install
fi

echo "Nettoyage des caches Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

ASSET_COMMAND="npm run dev"
PROCESS_NAMES="serve,vite,queue"
PROCESS_COLORS="blue,magenta,yellow"

if [ "${NODE_ENV:-development}" = "production" ]; then
    ASSET_COMMAND="npm run build"
    PROCESS_NAMES="serve,build,queue"
    PROCESS_COLORS="blue,green,yellow"
fi

echo "E-Client EPO démarré. Serveur: http://127.0.0.1:8000 | Queue: imports,default | Vite: hot reload"
echo "Appuyez sur Ctrl+C pour arrêter tous les processus."

npx concurrently \
    --kill-others-on-fail \
    --names "$PROCESS_NAMES" \
    --prefix "[{name}]" \
    --prefix-colors "$PROCESS_COLORS" \
    "php artisan serve --port=8000" \
    "$ASSET_COMMAND" \
    "php artisan queue:work database --queue=imports,default --timeout=7200 --memory=1024 --tries=1 --sleep=1"
