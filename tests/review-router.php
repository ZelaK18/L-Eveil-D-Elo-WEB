<?php
// Routeur réservé aux tests locaux : ne remplace pas les règles Apache/Nginx.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~(^|/)(app|bin|storage|textes|tests|php|\.[^/]*)(/|$)~i', $path)) {
    http_response_code(403);
    exit;
}
$root = $_SERVER['DOCUMENT_ROOT'];
$file = $root . $path;
if (is_file($file)) {
    return false;
}
$directory = str_starts_with($path, '/site/') ? '/site/' : '/';
$slug = substr($path, strlen($directory));
$target = match ($slug) {
    '' => 'index.php',
    'mentions-legales', 'confidentialite' => 'mentions-legales.php',
    'annuler', 'bientot' => $slug . '.php',
    'robots.txt' => 'robots.php',
    'sitemap.xml' => 'sitemap.php',
    default => '',
};
if ($target && is_file($root . $directory . $target)) {
    require $root . $directory . $target;
} else {
    http_response_code(404);
}
