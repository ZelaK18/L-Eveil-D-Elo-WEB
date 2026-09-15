<?php
// Servi sous l'adresse /robots.txt (voir .htaccess), avec le domaine de config.php.
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Disallow: /api/

Sitemap: <?= config('site.url') ?>sitemap.xml
