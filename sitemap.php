<?php
// Servi sous l'adresse /sitemap.xml (voir .htaccess), avec le domaine de config.php.
require __DIR__ . '/app/bootstrap.php';

$pages = [
    '' => ['index.php', 'textes/site-text.php', 'textes/booking-forms.php', 'app/intake.php'],
    'mentions-legales.php' => ['mentions-legales.php', 'textes/mentions-legales.php'],
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $path => $files): ?>
  <url>
    <loc><?= e(config('site.url') . $path) ?></loc>
    <lastmod><?= date('Y-m-d', max(array_map(fn(string $file) => filemtime(__DIR__ . '/' . $file), [...$files, 'app/config.php']))) ?></lastmod>
  </url>
<?php endforeach ?>
</urlset>
