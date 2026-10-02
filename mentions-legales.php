<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$legal = texts_file('mentions-legales');

// L'ancienne page PHP conserve les deux sections pour les liens déjà partagés.
$slug = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$sections = match ($slug) {
    'mentions-legales' => ['impressum' => 'mentions'],
    'confidentialite' => ['confidentialite' => 'confidentialite'],
    default => ['impressum' => 'mentions', 'confidentialite' => 'confidentialite'],
};
$page = count($sections) === 1 ? $legal[reset($sections)] : $legal;
$title = (string) ($page['google_titre'] ?? (($page['titre'] ?? '') . ' - ' . $site['name']));
$description = (string) ($page['google_description'] ?? $legal['google_description'] ?? '');
$canonical = $slug === 'confidentialite' ? 'confidentialite' : 'mentions-legales';

$links = [
    '{email}'     => '<a href="mailto:' . e($site['email']) . '">' . e($site['email']) . '</a>',
    '{telephone}' => '<a href="tel:' . e($site['phone']) . '">' . e($site['phone_display']) . '</a>',
    '{pfpdt}'     => '<a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">www.edoeb.admin.ch</a>',
];
// Retours à la ligne gardés, indentation du fichier de textes retirée.
$paragraph = fn(string $text) => strtr(nl2br(format_text(trim(preg_replace('/\n[ \t]+/', "\n", $text) ?? $text))), $links);
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<link rel="canonical" href="<?= e($site['url'] . $canonical) ?>">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <?php [$brandHref, $brandLabel] = ['./', "retour à l'accueil"]; require __DIR__ . '/app/partials/brand.php' ?>
    <a href="./" class="legal__back"><?= format_text($legal['retour'] ?? '') ?></a>
  </div>
</header>

<main class="legal">
  <div class="container legal__inner">

    <p class="eyebrow"><?= format_text($legal['surtitre'] ?? '') ?></p>
    <h1><?= format_text($page['titre'] ?? '') ?></h1>
    <p class="legal__update"><?= format_text($legal['mise_a_jour'] ?? '') ?></p>

    <nav class="legal__toc" aria-label="Sommaire">
      <a href="mentions-legales" class="button-secondary"<?= $slug === 'mentions-legales' ? ' aria-current="page"' : '' ?>><?= format_text($legal['mentions']['titre'] ?? '') ?></a>
      <a href="confidentialite" class="button-secondary"<?= $slug === 'confidentialite' ? ' aria-current="page"' : '' ?>><?= format_text($legal['confidentialite']['titre'] ?? '') ?></a>
    </nav>

    <?php foreach ($sections as $anchor => $part): ?>
    <section class="card legal__block" id="<?= $anchor ?>">
      <h2><?= format_text($legal[$part]['titre'] ?? '') ?></h2>
      <?php foreach ($legal[$part]['introduction'] ?? [] as $text): ?>
      <p><?= $paragraph($text) ?></p>
      <?php endforeach ?>

      <?php foreach ($legal[$part]['rubriques'] ?? [] as $heading => $blocks): ?>
      <h3><?= format_text($heading) ?></h3>
        <?php foreach ($blocks as $block): ?>
          <?php if (is_array($block)): ?>
      <ul>
            <?php foreach ($block as $item): ?>
        <li><?= $paragraph($item) ?></li>
            <?php endforeach ?>
      </ul>
          <?php else: ?>
      <p><?= $paragraph($block) ?></p>
          <?php endif ?>
        <?php endforeach ?>
      <?php endforeach ?>
    </section>
    <?php endforeach ?>

  </div>
</main>

<footer class="footer">
  <div class="container footer__bottom">
    <?php require __DIR__ . '/app/partials/copyright.php' ?>
    <p class="footer__legal"><a href="./"><?= format_text($legal['retour_accueil'] ?? '') ?></a></p>
  </div>
</footer>

</body>
</html>
