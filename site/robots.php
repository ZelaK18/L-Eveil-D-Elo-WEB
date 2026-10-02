<?php
// Servi sous l'adresse /robots.txt (voir .htaccess), avec le domaine de config.php.
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
$basePath = rtrim((string) parse_url(config('site.url'), PHP_URL_PATH), '/') . '/';
?>
User-agent: *
Disallow: <?= $basePath ?>api/

Sitemap: <?= config('site.url') ?>sitemap.xml
