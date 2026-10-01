<?php $intake = intake_texts() ?>
<h4 class="booking__heading" id="bookingIntakeTitle" tabindex="-1"><span class="booking__step">3</span>Préparer votre séance</h4>
<p class="booking__hint"><?= e($intake['intro']) ?></p>

<?php require __DIR__ . '/person-fields.php' ?>
<label class="field">
  <span>Date de naissance *</span>
  <input type="date" name="naissance" required autocomplete="bday" min="1900-01-01" max="<?= e((new DateTimeImmutable('today'))->modify('-18 years')->format('Y-m-d')) ?>">
</label>

<?php foreach ($intake['services'] as $serviceId => $definition): ?>
<fieldset class="booking__questionnaire" data-intake="<?= e($serviceId) ?>" hidden disabled>
  <legend><?= e($definition['title']) ?></legend>
  <?php foreach ($definition['fields'] as $key => $field): $fieldId = 'intake-' . $serviceId . '-' . $key; ?>
  <label class="field" for="<?= e($fieldId) ?>">
    <span><?= e($field['label']) ?><?= $field['required'] ? ' *' : ' (facultatif)' ?></span>
    <?php if (($field['type'] ?? '') === 'select'): ?>
    <select id="<?= e($fieldId) ?>" name="questionnaire[<?= e($key) ?>]"<?= $field['required'] ? ' required' : '' ?><?= isset($field['hint']) ? ' aria-describedby="' . e($fieldId) . '-hint"' : '' ?>>
      <option value="">Choisir…</option>
      <?php foreach ($field['options'] as $value => $label): ?>
      <option value="<?= e($value) ?>"><?= e($label) ?></option>
      <?php endforeach ?>
    </select>
    <?php else: ?>
    <textarea id="<?= e($fieldId) ?>" name="questionnaire[<?= e($key) ?>]" rows="3" maxlength="<?= (int) ($field['max'] ?? 2000) ?>"<?= $field['required'] ? ' required' : '' ?><?= isset($field['hint']) ? ' aria-describedby="' . e($fieldId) . '-hint"' : '' ?>></textarea>
    <?php endif ?>
    <?php if (isset($field['hint'])): ?>
    <small class="booking__hint" id="<?= e($fieldId) ?>-hint"><?= e($field['hint']) ?></small>
    <?php endif ?>
  </label>
  <?php endforeach ?>
</fieldset>

<?php endforeach ?>
<h4 class="booking__heading"><span class="booking__step">4</span>Votre accord</h4>
<p class="booking__hint">Relisez votre rendez-vous et les conditions avant de confirmer. Aucune réservation n’est effectuée avant votre validation finale.</p>
<p class="booking__recap" id="bookingFinalRecap"></p>
<?php foreach ($bookingOptions as $optionId => $option): ?>
<section class="booking__terms" data-terms="<?= e($optionId) ?>" data-version="<?= e(booking_terms_version($option)) ?>" data-price="<?= e(price_label($option)) ?>" data-format="<?= e($option['format']) ?>" hidden aria-label="Conditions de la prestation">
  <h5><?= $option['service'] === 'coaching' ? 'Votre contrat de coaching' : 'Le cadre de votre séance' ?></h5>
  <?php foreach (booking_terms($option) as $heading => $paragraph): ?>
  <h6><?= e($heading) ?></h6>
  <p><?= e($paragraph) ?></p>
  <?php endforeach ?>
  <p><a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener">Lire la politique de confidentialité (nouvel onglet)</a></p>
</section>
<?php endforeach ?>

<div class="booking__agreements">
  <?php foreach ($intake['consents'] as $key => $label): ?>
  <label class="consent">
    <input type="checkbox" name="<?= e($key) ?>" value="1" required>
    <span><?= e($label) ?> *</span>
  </label>
  <?php endforeach ?>
</div>
<p class="booking__hint">Votre nom et votre prénom ci-dessus identifient votre accord. Sa date et son heure sont enregistrées automatiquement lors de la validation.</p>
