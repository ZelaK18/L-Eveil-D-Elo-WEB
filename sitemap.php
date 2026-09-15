<?php
// Servi sous l'adresse /sitemap.xml (voir .htaccess), avec le domaine de config.php.
require __DIR__ . '/app/bootstrap.php';

$pages = [
    ''                     => 'index.php',
    'mentions-legales.php' => 'mentions-legales.php',
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $path => $file): ?>
  <url>
    <loc><?= e(config('site.url') . $path) ?></loc>
    <lastmod><?= date('Y-m-d', max(filemtime(__DIR__ . "/$file"), filemtime(__DIR__ . '/app/config.php'))) ?></lastmod>
  </url>
<?php endforeach ?>
</urlset>
