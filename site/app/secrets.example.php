<?php
// Copier ce fichier en secrets.php puis le remplir. secrets.php n'est jamais versionné.

return [
    // Clé aléatoire qui signe les formulaires et les liens d'annulation, par exemple le résultat de bin2hex(random_bytes(32)).
    // Ne plus la changer une fois le site en ligne : les liens d'annulation déjà envoyés ne marcheraient plus.
    'app_key' => '',

    // Client OAuth « Application Web » créé dans Google Cloud Console.
    'google' => [
        'client_id'     => '',
        'client_secret' => '',
    ],
];
