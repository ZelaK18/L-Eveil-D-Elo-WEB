<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$title = $site['name'] . ' | ' . site_text('bientot.titre_page');
$description = $site['name'] . '. ' . site_text('bientot.description');
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= e($site['url']) ?>">
</head>
<body class="soon">
<?php require __DIR__ . '/app/partials/icons.php' ?>

<div class="soon__decor" aria-hidden="true"></div>

<main class="soon__main">
  <div class="soon__identity">
    <img class="soon__logo" src="<?= e(asset('images/og-image.jpg')) ?>" width="1200" height="630" alt="<?= e($site['name']) ?>">
  </div>

  <p class="soon__status"><?= t('bientot.statut') ?></p>
  <h1 class="soon__title">
    <?= t('bientot.titre_ligne_1') ?>
    <em><?= t('bientot.titre_ligne_2') ?></em>
  </h1>
  <p class="soon__intro"><?= t('bientot.description') ?></p>

  <div class="divider-star soon__divider" aria-hidden="true">
    <i></i><svg width="12" height="12"><use href="#ico-star"/></svg><i></i>
  </div>

  <p class="soon__contact"><?= t('bientot.contact') ?></p>
  <div class="soon__actions">
    <a class="button-secondary" href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener noreferrer">
      <svg width="20" height="20" aria-hidden="true"><use href="#ico-insta"/></svg>
      <?= t('bientot.bouton_instagram') ?>
    </a>
  </div>
  <p class="soon__signature"><?= t('bientot.signature') ?></p>
</main>

<footer class="soon__footer">
  <?php require __DIR__ . '/app/partials/copyright.php' ?>
</footer>
</body>
</html>
