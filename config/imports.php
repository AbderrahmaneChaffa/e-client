<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retention des fichiers d'import
    |--------------------------------------------------------------------------
    |
    | Les lots terminés peuvent conserver leur fichier physique pendant une
    | durée limitée avant nettoyage automatique. Les données de diff et de
    | vérification restent en base et assurent la traçabilité.
    |
    */
    'cleanup' => [
        'enabled' => (bool) env('IMPORT_CLEANUP_ENABLED', true),
        'retention_days' => (int) env('IMPORT_RETENTION_DAYS', 30),
        'schedule_at' => env('IMPORT_CLEANUP_SCHEDULE_AT', '02:00'),
        'disk' => env('IMPORT_STORAGE_DISK', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Types d'import connus
    |--------------------------------------------------------------------------
    |
    | Liste de sécurité utilisée pour valider le filtre --type des commandes.
    |
    */
    'types' => ['factures', 'prestations', 'paiements'],
];
