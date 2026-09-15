<div class="form__row">
  <label class="field">
    <span><?= t('formulaire.nom') ?></span>
    <input type="text" name="nom" required autocomplete="family-name">
  </label>
  <label class="field">
    <span><?= t('formulaire.prenom') ?></span>
    <input type="text" name="prenom" required autocomplete="given-name">
  </label>
</div>

<div class="form__row">
  <label class="field">
    <span><?= t('formulaire.email') ?></span>
    <input type="email" name="email" required autocomplete="email">
  </label>
  <label class="field">
    <span><?= t('formulaire.telephone') ?></span>
    <input type="tel" name="telephone" required autocomplete="tel">
  </label>
</div>
