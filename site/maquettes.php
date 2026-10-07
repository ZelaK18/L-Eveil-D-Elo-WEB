<?php
// Propositions indépendantes : les textes et les tarifs restent ceux du site.
require __DIR__ . '/app/bootstrap.php';
header('X-Robots-Tag: noindex, nofollow');
$directions = [
    1 => ['La signature', 'Le portrait dans un médaillon, des détails étoilés et quatre cartes généreuses. Une évolution douce du site actuel.', 'Portrait en médaillon · cartes en duo', 'Le plus proche du site'],
    2 => ['Le carnet', 'Un accueil composé comme une page de carnet, un portrait encadré et le coaching mis en avant dans un grand chapitre.', 'Composition éditoriale · coaching mis en avant', 'Chaleureux et personnel'],
    3 => ['L’atelier', 'Le logo et le menu prennent place sur le côté. Le portrait et les prestations disposent de tout l’espace restant.', 'Menu latéral · panneaux de prestations', 'Une navigation différente'],
    4 => ['La mosaïque', 'Un accueil en blocs arrondis et des prestations de tailles différentes, assemblées dans une composition souple.', 'Blocs asymétriques · formes arrondies', 'Doux et contemporain'],
    5 => ['La parenthèse', 'Un accueil centré, une petite photo en médaillon et des prestations présentées en lignes aérées.', 'Accueil centré · catalogue à filets', 'Simple et apaisant'],
    6 => ['La rencontre', 'Le portrait ouvre la page à gauche. Les prestations se déplient une à une, comme les sujets d’une conversation.', 'Portrait à gauche · prestations à déplier', 'La relation au premier plan'],
    7 => ['Le chemin', 'Une grande introduction encadrée, des raccourcis vers les prestations et un parcours alterné pour les découvrir.', 'Accueil encadré · parcours alterné', 'Une découverte guidée'],
    8 => ['L’écrin', 'Le portrait et les mots réunis dans un même cadre. Un sélecteur permet ensuite de découvrir chaque prestation à son rythme.', 'Accueil dans un cadre · prestations par onglets', 'Compact et enveloppant'],
];
$raw = $_GET['v'] ?? '';
$variant = is_string($raw) && preg_match('/^[1-8]$/D', $raw) ? (int) $raw : 0;
$site = config('site');
$services = config('services');
$title = $variant ? $directions[$variant][0] . ' — ' . $site['name'] : 'Huit mises en page pour L’éveil d’Elo';
function mock_paragraphs(string $text): void {
    foreach (preg_split('/\R\s*\R/u', $text) as $paragraph) echo '<p>' . format_text($paragraph) . '</p>';
}
function mock_icon(string $name, int $size = 24): void {
    echo '<svg width="' . $size . '" height="' . $size . '" aria-hidden="true"><use href="#' . e($name) . '"/></svg>';
}
function mock_brand(string $href): void {
    echo '<a class="wordmark" href="' . e($href) . '"><img src="images/logo-embleme.webp" width="65" height="65" alt=""><span class="wordmark__text">' . e(config('site.name')) . '<small>' . t('qui_suis_je.role') . '</small></span></a>';
}
function mock_service_body(array $service): void { ?>
    <div class="service__body"><?php mock_paragraphs($service['text']) ?><ul><?php foreach ($service['points'] as $point): ?><li><?= format_text($point) ?></li><?php endforeach ?></ul></div>
<?php }
function mock_service_booking(array $service): void { ?>
    <div class="service__booking">
      <?php if (!empty($service['offers'])): ?>
        <div class="offers"><?php foreach ($service['offers'] as $offer): ?><details><summary><span><?= e($offer['nom']) ?></span><span><?= e($offer['tarif']) ?></span></summary><div><p><?= format_text($offer['finalite']) ?></p><p><?= e($offer['duree']) ?> · <?= e($offer['format']) ?></p><?php if (isset($offer['tarif_habituel'])): ?><p>Tarif habituel : <?= e($offer['tarif_habituel']) ?></p><?php endif ?><a href="./#rendez-vous" class="text-link"><?= t('prestations.lien_reserver') ?> <span aria-hidden="true">↗</span></a></div></details><?php endforeach ?></div>
      <?php elseif ($service['available']): ?><strong class="price"><?= e(price_label($service)) ?></strong><a class="text-link" href="./#rendez-vous"><?= t('prestations.lien_reserver') ?> <span aria-hidden="true">↗</span></a>
      <?php else: ?><p><?= t('prestations.bientot') ?></p><?php endif ?>
    </div>
<?php }
?>
<!doctype html>
<html lang="fr-CH">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?></title>
  <link rel="icon" href="images/favicon.png">
  <link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/maquettes.css')) ?>">
  <script src="<?= e(asset('js/maquettes.js')) ?>" defer></script>
</head>
<body class="<?= $variant ? 'concept concept--' . $variant : 'gallery' ?>">
<?php require __DIR__ . '/app/partials/icons.php' ?>
<a href="#contenu" class="skip-link">Aller au contenu</a>
<?php if (!$variant): ?>
  <header class="gallery__header"><?php mock_brand('./') ?><a class="text-link" href="./">Voir le site actuel ↗</a></header>
  <main class="gallery__main" id="contenu">
    <div class="gallery__opening"><p class="kicker">L’éveil d’Elo · explorations de mise en page</p>
    <span class="flourish" aria-hidden="true"><?php mock_icon('ico-star', 14) ?></span>
    <h1>Huit mises en page.<br><em>Un même univers.</em></h1>
    <p class="gallery__intro">Votre logo, les tons beige et brun, Cormorant Garamond et Montserrat, vos textes et le portrait d’Elodie. Huit façons de les réunir, dans la douceur du site actuel.</p>
    <div class="gallery__traits"><span>La même palette</span><span>Les mêmes polices</span><span>Votre portrait intégré</span></div></div>
    <div class="gallery__tools"><p>Comparez l’accueil <em>et</em> la présentation des prestations.</p><div class="view-switch" role="group" aria-label="Aperçus affichés" hidden><button type="button" data-preview-view="hero" aria-pressed="true">L’accueil</button><button type="button" data-preview-view="services" aria-pressed="false">Les prestations</button></div></div>
    <div class="gallery__grid">
    <?php foreach ($directions as $number => [$name, $description, $layout, $mood]): ?>
      <article class="direction direction--<?= $number ?>">
        <a class="direction__preview" href="?v=<?= $number ?>" aria-label="Voir la maquette <?= $number ?> : <?= e($name) ?>">
          <img src="<?= e(asset('images/maquette-' . $number . '.png')) ?>" data-hero="<?= e(asset('images/maquette-' . $number . '.png')) ?>" data-services="<?= e(asset('images/maquette-' . $number . '-prestations.png')) ?>" data-name="<?= e($name) ?>" width="1440" height="1050" alt="Accueil de la composition <?= e($name) ?>" loading="lazy">
        </a>
        <div class="direction__body"><p class="kicker">0<?= $number ?> / <?= e($mood) ?></p><h2><?= e($name) ?></h2><p><?= e($description) ?></p><p class="layout-note"><?= e($layout) ?></p><a class="text-link" href="?v=<?= $number ?>">Explorer cette maquette <span aria-hidden="true">↗</span></a></div>
      </article>
    <?php endforeach ?>
    </div>
    <aside class="gallery__note"><h2>Pour vous aider à choisir</h2><p><strong>La signature</strong> reste la plus proche du site actuel. <strong>Le carnet</strong> met davantage votre personnalité en avant. <strong>La rencontre</strong> simplifie la lecture des prestations. Vous pouvez aussi retenir l’accueil d’une proposition et les prestations d’une autre.</p><p>Chaque maquette se parcourt entièrement sur ordinateur et sur téléphone. Les réservations renvoient au parcours existant.</p></aside>
  </main>
<?php else: ?>
  <aside class="preview-bar" aria-label="Navigation entre les maquettes">
    <a href="maquettes.php">← Les 8 maquettes</a>
    <span>0<?= $variant ?> · <?= e($directions[$variant][0]) ?></span>
    <nav aria-label="Choisir une maquette"><?php foreach ($directions as $number => $direction): ?><a href="?v=<?= $number ?>" title="<?= e($direction[0]) ?>" aria-label="Maquette <?= $number ?> : <?= e($direction[0]) ?>"<?= $number === $variant ? ' aria-current="page"' : '' ?>><?= $number ?></a><?php endforeach ?></nav>
  </aside>
  <div class="concept__shell">
    <header class="masthead">
      <?php mock_brand('#accueil') ?>
      <nav aria-label="Navigation principale"><a href="#prestations"><?= t('menu.prestations') ?></a><a href="#elodie"><?= t('qui_suis_je.surtitre') ?></a><a href="#bons-cadeaux"><?= t('menu.bons_cadeaux') ?></a><a href="#contact"><?= t('menu.contact') ?></a></nav>
      <a class="small-cta" href="#rendez-vous"><?= t('menu.rendez_vous') ?> <span aria-hidden="true">↗</span></a>
    </header>
    <main id="contenu">
      <section class="opening" id="accueil" aria-labelledby="opening-title">
        <div class="opening__copy"><p class="kicker"><?= t('accueil.surtitre') ?></p><span class="flourish" aria-hidden="true"><?php mock_icon('ico-star', 12) ?></span><h1 id="opening-title"><?= t('accueil.titre_ligne_1') ?><br><span><?= t('accueil.titre_ligne_2') ?></span></h1><p class="opening__intro"><?= t('accueil.texte') ?></p><div class="actions"><a class="cta" href="#rendez-vous"><?= t('accueil.bouton_rendez_vous') ?> <span aria-hidden="true">↗</span></a><a class="text-link" href="#prestations"><?= t('accueil.bouton_prestations') ?></a></div></div>
        <figure class="opening__portrait"><img src="<?= e(asset('images/portrait-elodie.webp')) ?>" width="880" height="1100" alt="<?= e(site_text('accueil.description_photo')) ?>" fetchpriority="high"><figcaption><span><?= t('qui_suis_je.nom') ?></span><span><?= t('accueil.badge_photo') ?></span></figcaption></figure>
        <ul class="values"><?php foreach (site_text('accueil.valeurs') as $value): ?><li><?php mock_icon('ico-star', 9) ?><?= format_text($value) ?></li><?php endforeach ?></ul>
      </section>
      <?php if ($variant === 7): ?><nav class="journey-nav" aria-label="Découvrir les prestations"><?php foreach ($services as $id => $service): ?><a href="#service-<?= e($id) ?>"><?php mock_icon($service['icon'], 32) ?><span><?= e($service['name']) ?></span><span aria-hidden="true">↓</span></a><?php endforeach ?></nav><?php endif ?>
      <section class="story section" id="elodie" aria-labelledby="story-title">
        <header><p class="kicker"><?= t('qui_suis_je.surtitre') ?></p><h2 id="story-title"><?= t('qui_suis_je.nom') ?><span class="story__role"><?= t('qui_suis_je.role') ?></span></h2></header>
        <div class="story__body"><?php foreach (site_text('qui_suis_je.paragraphes') as $paragraph): ?><p><?= format_text($paragraph) ?></p><?php endforeach ?><p class="signature"><?= t('qui_suis_je.signature') ?></p></div>
      </section>
      <section class="offerings section" id="prestations" aria-labelledby="services-title">
        <header class="section-heading"><div><p class="kicker"><?= t('prestations.surtitre') ?></p><h2 id="services-title"><?= t('prestations.titre') ?></h2></div><p><?= t('prestations.texte') ?></p></header>
        <?php if ($variant === 8): ?><nav class="service-tabs" aria-label="Choisir une prestation" data-service-tabs><?php foreach ($services as $id => $service): ?><a href="#service-<?= e($id) ?>" id="tab-<?= e($id) ?>"><?php mock_icon($service['icon'], 26) ?><?= e($service['name']) ?></a><?php endforeach ?></nav><?php endif ?>
        <div class="service-list">
        <?php $n = 0; foreach ($services as $id => $service): $n++; ?>
          <article class="service service--<?= e($id) ?>" id="service-<?= e($id) ?>">
            <?php if ($variant === 6): ?><details class="service-accordion"<?= $id === 'coaching' ? ' open' : '' ?>><summary><span class="service__number" aria-hidden="true">0<?= $n ?></span><?php mock_icon($service['icon'], 42) ?><h3><?= format_text($service['name']) ?></h3><span class="accordion-plus" aria-hidden="true">+</span></summary><div class="accordion-content"><p class="service__format"><?= $service['available'] ? e($service['format'] ?? '') : t('prestations.a_venir') ?></p><?php mock_service_body($service); mock_service_booking($service) ?></div></details>
            <?php else: ?>
            <span class="service__number" aria-hidden="true"><?php mock_icon($service['icon'], 42) ?><span>0<?= $n ?></span></span>
            <div class="service__heading"><h3><?= format_text($service['name']) ?></h3><?php if (!$service['available']): ?><span class="service__format"><?= t('prestations.a_venir') ?></span><?php elseif (isset($service['format'])): ?><span class="service__format"><?= e($service['format']) ?></span><?php endif ?></div>
            <?php mock_service_body($service); mock_service_booking($service) ?>
            <?php endif ?>
          </article>
        <?php endforeach ?>
        </div>
      </section>
      <section class="gifting section" id="bons-cadeaux" aria-labelledby="gift-title">
        <div class="gift-paper" aria-hidden="true"><img src="images/logo-embleme.webp" width="65" height="65" alt="" loading="lazy"><span><?= e($site['name']) ?></span><strong><?= t('bons_cadeaux.image_titre') ?></strong><span><?= t('bons_cadeaux.image_texte') ?></span><span><?= t('bons_cadeaux.image_validite') ?></span></div>
        <div><p class="kicker"><?= t('bons_cadeaux.surtitre') ?></p><h2 id="gift-title"><?= t('bons_cadeaux.titre') ?></h2><?php foreach (site_text('bons_cadeaux.paragraphes') as $paragraph): ?><p><?= format_text($paragraph) ?></p><?php endforeach ?><ol class="gift-steps"><?php foreach (site_text('bons_cadeaux.etapes') as $step): ?><li><strong><?= format_text($step['titre']) ?></strong><p><?= format_text($step['texte']) ?></p></li><?php endforeach ?></ol><a class="cta" href="./#demande"><?= t('bons_cadeaux.bouton') ?> <span aria-hidden="true">↗</span></a></div>
      </section>
      <section class="booking section" id="rendez-vous" aria-labelledby="booking-title"><p class="kicker"><?= t('rendez_vous.surtitre') ?></p><h2 id="booking-title"><?= t('rendez_vous.titre') ?></h2><p><?= t('rendez_vous.texte') ?></p><p><?= t('rendez_vous.en_ligne_texte') ?></p><a class="cta" href="./#rendez-vous"><?= t('rendez_vous.en_ligne_titre') ?> <span aria-hidden="true">↗</span></a></section>
      <section class="contact section" id="contact" aria-labelledby="contact-title"><div><p class="kicker"><?= t('contact.surtitre') ?></p><h2 id="contact-title"><?= t('contact.titre') ?></h2><p><?= t('contact.texte') ?></p></div><div class="contact__links"><a class="text-link" href="mailto:<?= e($site['email']) ?>"><?= t('contact.email_texte') ?> ↗</a><a href="tel:<?= e($site['phone']) ?>"><?= e($site['phone_display']) ?></a><p><?= t('contact.telephone_texte') ?></p><a class="text-link" href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener"><?= t('contact.instagram_titre') ?> ↗</a><p><?= t('contact.instagram_texte') ?></p></div></section>
    </main>
    <footer class="concept-footer"><?php mock_brand('#accueil') ?><p><?= t('pied_de_page.presentation') ?></p><p class="disclaimer"><?= t('pied_de_page.avertissement') ?></p><nav aria-label="Informations légales"><a href="mentions-legales"><?= t('pied_de_page.mentions_legales') ?></a><a href="confidentialite"><?= t('pied_de_page.confidentialite') ?></a><a href="maquettes.php">Les huit maquettes</a></nav><small>© <?= date('Y') ?> <?= e($site['name']) ?> · <?= t('pied_de_page.droits') ?></small></footer>
  </div>
<?php endif ?>
</body>
</html>
