<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$legal = texts_file('mentions-legales');
$title = (string) ($legal['google_titre'] ?? '');
$description = (string) ($legal['google_description'] ?? '');

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
<link rel="canonical" href="<?= e($site['url']) ?>mentions-legales.php">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a href="./" class="brand" aria-label="L'éveil d'Elo, retour à l'accueil">
      <span class="mark brand__mark" role="img" aria-label="Logo L'éveil d'Elo"></span>
      <span class="brand__name">L'éveil d'Elo</span>
    </a>
    <a href="./" class="legal__back"><?= format_text($legal['retour'] ?? '') ?></a>
  </div>
</header>

<main class="legal">
  <div class="container legal__inner">

    <p class="eyebrow"><?= format_text($legal['surtitre'] ?? '') ?></p>
    <h1><?= format_text($legal['titre'] ?? '') ?></h1>
    <p class="legal__update"><?= format_text($legal['mise_a_jour'] ?? '') ?></p>

    <nav class="legal__toc" aria-label="Sommaire">
      <a href="#impressum" class="button-secondary"><?= format_text($legal['mentions']['titre'] ?? '') ?></a>
      <a href="#confidentialite" class="button-secondary"><?= format_text($legal['confidentialite']['titre'] ?? '') ?></a>
    </nav>

    <?php foreach (['impressum' => 'mentions', 'confidentialite' => 'confidentialite'] as $anchor => $part): ?>
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
    <p>&copy; <?= date('Y') ?> L'éveil d'Elo - <?= t('pied_de_page.droits') ?></p>
    <p class="footer__legal"><a href="./"><?= format_text($legal['retour_accueil'] ?? '') ?></a></p>
  </div>
</footer>

</body>
</html>
