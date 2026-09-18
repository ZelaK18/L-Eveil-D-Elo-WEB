<?php /** @var string $consent clé du texte d'accord dans site-text.php */ ?>
<label class="consent">
  <input type="checkbox" name="consent" required>
  <span><?= t($consent) ?> (<a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener"><?= t('formulaire.lien_confidentialite') ?></a>).</span>
</label>
