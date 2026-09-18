<?php

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Valeur d'un tableau imbriqué : dig($config, 'site.name') vaut $config['site']['name'], ou null.
function dig(array $data, string $path): mixed
{
    return array_reduce(explode('.', $path), fn($value, $key) => $value[$key] ?? null, $data);
}

function config(string $path): mixed
{
    static $config;
    $config ??= with_site_texts(require __DIR__ . '/config.php');
    return dig($config, $path);
}

function secret(string $path): mixed
{
    static $secrets;
    $secrets ??= is_file(__DIR__ . '/secrets.php') ? require __DIR__ . '/secrets.php' : [];
    return dig($secrets, $path);
}

// Fichier du dossier textes/. Si une faute de frappe le rend illisible, la dernière version valide sert à la place.
function texts_file(string $name): array
{
    static $files = [];
    if (isset($files[$name])) {
        return $files[$name];
    }

    $file = ROOT_DIR . "/textes/$name.php";
    try {
        $texts = require $file;
        if (!is_array($texts)) {
            throw new UnexpectedValueException('le fichier ne renvoie aucun texte');
        }
        if (@filemtime($file) > @filemtime(storage_path("backup-$name.php"))) {
            storage_write("backup-$name", $texts);
        }
    } catch (Throwable $e) {
        error_log("textes/$name.php illisible, dernière version valide utilisée : " . $e->getMessage());
        $texts = storage_read("backup-$name");
    }
    return $files[$name] = $texts;
}

function site_text(string $path): mixed
{
    return dig(texts_file('site-text'), $path);
}

// Texte prêt pour la page : **mot** en gras, *mot* en italique, espace insécable avant ? ! : ;
function format_text(string $text): string
{
    return preg_replace(['/\*\*(.+?)\*\*/u', '/\*(.+?)\*/u', '/ ([?!:;])/u'], ['<strong>$1</strong>', '<em>$1</em>', "\u{00A0}$1"], e($text));
}

function t(string $path): string
{
    return format_text((string) site_text($path));
}

function message(string $key): string
{
    return (string) site_text("messages.$key");
}

// Les cartes de textes/site-text.php complètent les prestations de config.php : nom, texte, prix, durée…
function with_site_texts(array $config): array
{
    $fields = ['nom' => 'name', 'texte' => 'text', 'points' => 'points', 'format' => 'format', 'duree' => 'duration', 'prix' => 'price', 'prix_par' => 'price_unit', 'disponible' => 'available', 'offres' => 'offers'];
    foreach (array_keys($config['services']) as $id) {
        foreach ($fields as $from => $to) {
            $value = site_text("prestations.cartes.$id.$from");
            if ($value !== null) {
                $config['services'][$id][$to] = $value;
            }
        }
    }
    return $config;
}

// ?v= oblige le navigateur à recharger le fichier dès qu'il change.
function asset(string $path): string
{
    return $path . '?v=' . @filemtime(ROOT_DIR . '/' . $path);
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Stockage : JSON derrière un « exit » PHP, illisible même si le serveur expose le dossier.
const STORAGE_GUARD = "<?php exit; ?>\n";

function storage_path(string $file): string
{
    $dir = ROOT_DIR . '/storage';
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    return "$dir/$file";
}

function storage_read(string $name): array
{
    $raw = @file_get_contents(storage_path("$name.php"));
    return $raw === false ? [] : (json_decode(substr($raw, strlen(STORAGE_GUARD)), true) ?: []);
}

function storage_write(string $name, array $data): void
{
    $temp = storage_path($name . '.' . bin2hex(random_bytes(6)) . '.php');
    file_put_contents($temp, STORAGE_GUARD . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    rename($temp, storage_path("$name.php"));
}

/** @return resource */
function lock(string $name)
{
    $handle = fopen(storage_path("$name.lock"), 'c');
    flock($handle, LOCK_EX);
    return $handle;
}

function with_lock(string $name, callable $callback): mixed
{
    $handle = lock($name);
    try {
        return $callback();
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

// Verrou gardé jusqu'à la fin de la requête.
function hold_lock(string $name): void
{
    static $handles = [];
    $handles[$name] = lock($name);
}

function input(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim(mb_scrub($value, 'UTF-8')) : '';
}

function input_list(string $key): array
{
    $values = $_POST[$key] ?? [];
    return is_array($values) ? array_filter($values, 'is_string') : [];
}

function single_line(string $text, int $max = 150): string
{
    return mb_substr(trim(preg_replace('/\s+/u', ' ', $text) ?? ''), 0, $max);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function form_response(bool $ok, string $message, int $status = 200, array $extra = []): never
{
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_response(['ok' => $ok, 'message' => $message] + $extra, $ok ? 200 : $status);
    }
    header('Location: ../?demande=' . ($ok ? 'ok' : 'erreur') . '#rendez-vous', true, 303);
    exit;
}

function app_key(): string
{
    return (string) (secret('app_key') ?: hash('sha256', __DIR__));
}

// Jeton horodaté et signé : écarte les robots qui postent sans charger la page ou en moins de 3 secondes.
function form_token(): string
{
    $time = (string) time();
    return $time . '.' . hash_hmac('sha256', $time, app_key());
}

function valid_form_token(string $token): bool
{
    [$time, $signature] = array_pad(explode('.', $token, 2), 2, '');
    $age = time() - (int) $time;
    return ctype_digit($time) && $age >= 3 && $age <= 2 * 86400
        && hash_equals(hash_hmac('sha256', $time, app_key()), $signature);
}

function rate_limit_key(string $action): string
{
    return $action . ':' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', app_key());
}

function too_many_attempts(string $action, int $max, int $seconds): bool
{
    $times = storage_read('rate-limit')[rate_limit_key($action)] ?? [];
    return count(array_filter($times, fn(int $time) => $time > time() - $seconds)) >= $max;
}

// Seuls les envois aboutis comptent : une faute de saisie ou un créneau déjà pris ne bloque personne.
function record_attempt(string $action): void
{
    with_lock('rate-limit', function () use ($action): void {
        $now = time();
        $hits = array_filter(array_map(
            fn(array $times) => array_values(array_filter($times, fn(int $time) => $time > $now - 86400)),
            storage_read('rate-limit')
        ));
        $hits[rate_limit_key($action)][] = $now;
        storage_write('rate-limit', $hits);
    });
}

function guard_form(string $action, int $max, int $seconds): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        form_response(false, 'Méthode non autorisée.', 405);
    }
    if (input('site_web') !== '') {
        form_response(true, 'Merci !');
    }
    if (!valid_form_token(input('jeton'))) {
        form_response(false, message('page_expiree'), 422);
    }
    // Un envoi à la fois : des envois simultanés ne peuvent pas tous passer la limite avant d'être comptés.
    hold_lock("form-$action");
    if (too_many_attempts($action, $max, $seconds)) {
        form_response(false, message('trop_envois'), 429);
    }
}

function read_person(): array
{
    $person = [
        'nom'       => single_line(input('nom'), 60),
        'prenom'    => single_line(input('prenom'), 60),
        'email'     => single_line(input('email')),
        'telephone' => single_line(input('telephone'), 40),
        'message'   => mb_substr(input('message'), 0, 5000),
    ];
    if (in_array('', [$person['nom'], $person['prenom'], $person['telephone'], input('consent')], true)) {
        form_response(false, message('champs_obligatoires'), 422);
    }
    if (!filter_var($person['email'], FILTER_VALIDATE_EMAIL)) {
        form_response(false, message('email_invalide'), 422);
    }
    // Nom, prénom et téléphone sont repris dans l'e-mail envoyé à l'adresse saisie : pas de lien ni de balise.
    if (preg_match('#https?:|www\.|[<>/@]|\.(com|net|org|ch|fr|de|io|co|ru|xyz|info|biz|link|top)\b#i', "{$person['prenom']} {$person['nom']}")) {
        form_response(false, message('nom_invalide'), 422);
    }
    if (!preg_match('/^\+?[0-9 ().-]{6,25}$/', $person['telephone'])) {
        form_response(false, message('telephone_invalide'), 422);
    }
    return $person;
}
