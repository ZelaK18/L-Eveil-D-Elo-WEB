<div class="form__row">
  <label class="field">
    <span><?= t('formulaire.nom') ?> *</span>
    <input type="text" name="nom" maxlength="60" required autocomplete="family-name">
  </label>
  <label class="field">
    <span><?= t('formulaire.prenom') ?> *</span>
    <input type="text" name="prenom" maxlength="60" required autocomplete="given-name">
  </label>
</div>

<div class="form__row">
  <label class="field">
    <span><?= t('formulaire.email') ?> *</span>
    <input type="email" name="email" maxlength="150" required autocomplete="email">
  </label>
  <label class="field">
    <span><?= t('formulaire.telephone') ?> *</span>
    <input type="tel" name="telephone" maxlength="25" required autocomplete="tel">
  </label>
</div>
