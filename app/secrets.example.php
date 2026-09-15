<?php
// Copier ce fichier en secrets.php puis le remplir. secrets.php n'est jamais versionné.

return [
    // Clé aléatoire qui signe les formulaires, par exemple le résultat de bin2hex(random_bytes(32)).
    'app_key' => '',

    // Client OAuth « Application Web » créé dans Google Cloud Console.
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],
];
