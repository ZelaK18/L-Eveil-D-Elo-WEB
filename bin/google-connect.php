<?php
// Usage : php bin/google-connect.php [adresse de redirection déclarée dans Google Cloud Console]
// Écrit storage/google-token.php, à copier sur le serveur en production.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

$redirectUri = $argv[1] ?? 'https://leveildelo.app/admin/';
$state = bin2hex(random_bytes(16));
$calendarScope = 'https://www.googleapis.com/auth/calendar.events';
$gmailScope = 'https://www.googleapis.com/auth/gmail.send';

echo "1. Ouvrez cette adresse, choisissez le compte de l'agenda et cochez toutes les autorisations :\n\n";
echo 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id'     => secret('google.client_id'),
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => "openid email $calendarScope $gmailScope",
    'access_type'   => 'offline',
    'prompt'        => 'consent',
    'state'         => $state,
]), "\n\n";
echo "2. Google renvoie ensuite vers $redirectUri : la page affichée n'a pas d'importance.\n";
echo "   Copiez l'adresse complète de la barre du navigateur, collez-la ici puis appuyez sur Entrée :\n> ";

parse_str((string) parse_url(trim((string) fgets(STDIN)), PHP_URL_QUERY), $query);
if (!hash_equals($state, (string) ($query['state'] ?? '')) || empty($query['code'])) {
    fwrite(STDERR, "\nCette adresse ne correspond pas à la connexion en cours : relancez le script.\n");
    exit(1);
}

try {
    $response = http_request('POST', 'https://oauth2.googleapis.com/token', form: [
        'grant_type'    => 'authorization_code',
        'code'          => $query['code'],
        'client_id'     => secret('google.client_id'),
        'client_secret' => secret('google.client_secret'),
        'redirect_uri'  => $redirectUri,
    ]);
} catch (GoogleError $e) {
    fwrite(STDERR, "\nGoogle a refusé la connexion : {$e->getMessage()}\n");
    exit(1);
}

$scopes = explode(' ', $response['scope'] ?? '');
if (empty($response['refresh_token']) || !in_array($calendarScope, $scopes, true)) {
    fwrite(STDERR, "\nAccès incomplet : relancez le script et cochez l'accès à Google Agenda.\n");
    exit(1);
}

$claims = json_decode(base64_decode(strtr(explode('.', $response['id_token'] ?? '')[1] ?? '', '-_', '+/')), true) ?: [];
storage_write('google-token', [
    'email'         => $claims['email'] ?? null,
    'scopes'        => $scopes,
    'refresh_token' => $response['refresh_token'],
    'access_token'  => $response['access_token'],
    'expires_at'    => time() + (int) $response['expires_in'] - 60,
    'connected_at'  => date(DATE_ATOM),
]);

echo "\nCompte connecté : ", $claims['email'] ?? 'adresse inconnue', "\n";
echo 'E-mails envoyés par Gmail : ', in_array($gmailScope, $scopes, true) ? 'oui' : 'non, cochez aussi cet accès', "\n";
