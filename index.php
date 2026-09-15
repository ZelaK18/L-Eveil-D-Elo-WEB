<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$services = config('services');
$prices = array_column(array_filter($services, fn(array $service) => $service['available']), 'price');
$title = (string) site_text('google.titre');
$description = (string) site_text('google.description');
$gift = (string) site_text('rendez_vous.demande_bon_cadeau');

$antispam = '<input type="hidden" name="jeton" value="' . e(form_token()) . '">'
    . '<label class="honeypot" aria-hidden="true">Site web <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>';

// Retour du formulaire de demande quand il a été envoyé sans JavaScript.
[$requestState, $requestMessage] = match ($_GET['demande'] ?? '') {
    'ok'     => ['is-ok', message('demande_envoyee')],
    'erreur' => ['is-error', message('envoi_echoue')],
    default  => ['', ''],
};

$weekdays = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$schema = [
    '@context'           => 'https://schema.org',
    '@type'              => 'ProfessionalService',
    'name'               => $site['name'],
    'description'        => $description,
    '@id'                => $site['url'] . '#entreprise',
    'url'                => $site['url'],
    'image'              => $site['url'] . 'images/og-image.jpg',
    'logo'               => $site['url'] . 'images/logo-embleme.png',
    'founder'            => ['@type' => 'Person', 'name' => 'Elodie Fauquex', 'jobTitle' => 'Coach en spiritualité'],
    'telephone'          => $site['phone'],
    'email'              => $site['email'],
    'priceRange'         => $prices ? 'CHF ' . min($prices) . '.- - CHF ' . max($prices) . '.-' : 'Sur demande',
    'currenciesAccepted' => 'CHF',
    'address'            => ['@type' => 'PostalAddress', 'addressRegion' => 'Fribourg', 'addressCountry' => 'CH'],
    'areaServed'         => [
        ['@type' => 'AdministrativeArea', 'name' => 'Suisse romande'],
        ['@type' => 'Country', 'name' => 'Switzerland'],
    ],
    'availableLanguage'  => 'fr',
    'sameAs'             => [$site['instagram']],
    'openingHoursSpecification' => array_merge(...array_map(fn(array $group) => array_map(fn(array $range) => [
        '@type'     => 'OpeningHoursSpecification',
        'dayOfWeek' => array_slice($weekdays, $group['from'], $group['to'] - $group['from'] + 1),
        'opens'     => $range[0],
        'closes'    => $range[1],
    ], $group['ranges']), opening_groups())),
    'hasOfferCatalog' => [
        '@type'           => 'OfferCatalog',
        'name'            => 'Prestations',
        'itemListElement' => array_values(array_map(fn(array $service) => [
            '@type'       => 'Offer',
            'itemOffered' => ['@type' => 'Service', 'name' => $service['name'], 'description' => $service['summary']],
        ] + ($service['available']
            ? ['price' => (string) $service['price'], 'priceCurrency' => 'CHF']
            : ['availability' => 'https://schema.org/PreOrder']), $services)),
    ],
];
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<meta name="author" content="Elodie Fauquex">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="theme-color" content="#F7EDDD">
<link rel="canonical" href="<?= e($site['url']) ?>">

<meta property="og:type" content="website">
<meta property="og:locale" content="fr_CH">
<meta property="og:site_name" content="<?= e($site['name']) ?>">
<meta property="og:title" content="<?= e(site_text('google.titre_partage')) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($site['url']) ?>">
<meta property="og:image" content="<?= e($site['url']) ?>images/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Logo de L'éveil d'Elo">
<meta name="twitter:card" content="summary_large_image">

<link rel="apple-touch-icon" href="images/favicon.png">

<noscript><style>.reveal { opacity: 1; }</style></noscript>

<script type="application/ld+json">
<?= json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>

</script>
</head>
<body>

<?php require __DIR__ . '/app/partials/icons.php' ?>

<header class="header" id="header">
  <div class="header__inner">
    <a href="#accueil" class="brand" aria-label="L'éveil d'Elo, retour en haut">
      <span class="mark brand__mark" role="img" aria-label="Logo L'éveil d'Elo"></span>
      <span class="brand__name">L'éveil d'Elo</span>
    </a>

    <nav class="nav" id="nav" aria-label="Navigation principale">
      <ul class="nav__list">
        <li><a href="#accueil"      class="nav__link is-active"><?= t('menu.accueil') ?></a></li>
        <li><a href="#prestations"  class="nav__link"><?= t('menu.prestations') ?></a></li>
        <li><a href="#bons-cadeaux" class="nav__link"><?= t('menu.bons_cadeaux') ?></a></li>
        <li><a href="#contact"      class="nav__link"><?= t('menu.contact') ?></a></li>
        <li><a href="#rendez-vous"  class="nav__link nav__link--cta"><svg width="11" height="11" aria-hidden="true"><use href="#ico-star"/></svg><?= t('menu.rendez_vous') ?></a></li>
      </ul>
    </nav>

    <button class="burger" id="burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main>
<section class="hero" id="accueil">
  <div class="sky" aria-hidden="true">
    <svg width="18" height="18" style="--x:7%;--y:12%"><use href="#ico-star"/></svg>
    <svg width="11" height="11" style="--x:47%;--y:26%;--d:1.1s"><use href="#ico-star"/></svg>
    <svg width="14" height="14" style="--x:3%;--y:62%;--d:2.2s"><use href="#ico-star"/></svg>
    <svg width="9" height="9" style="--x:52%;--y:78%;--d:.6s"><use href="#ico-star"/></svg>
    <svg width="13" height="13" style="--x:94%;--y:8%;--d:1.7s"><use href="#ico-star"/></svg>
  </div>

  <div class="container hero__grid stagger">

    <div class="hero__text reveal">
      <p class="eyebrow"><?= t('accueil.surtitre') ?></p>
      <h1><?= t('accueil.titre_ligne_1') ?><br><?= t('accueil.titre_ligne_2') ?></h1>
      <p class="lead"><?= t('accueil.texte') ?></p>
      <div class="hero__actions">
        <a href="#rendez-vous" class="button-primary"><?= t('accueil.bouton_rendez_vous') ?></a>
        <a href="#prestations" class="button-secondary"><?= t('accueil.bouton_prestations') ?></a>
      </div>

      <ul class="hero__values">
        <?php foreach (site_text('accueil.valeurs') ?? [] as $value): ?>
        <li><?= format_text($value) ?></li>
        <?php endforeach ?>
      </ul>
    </div>

    <div class="hero__media reveal">
      <figure class="portrait">
        <img src="images/test-pp.jpg" alt="<?= e(site_text('accueil.description_photo')) ?>" class="portrait__img" fetchpriority="high">
      </figure>
      <svg class="portrait__stars" viewBox="0 0 100 100" aria-hidden="true">
        <use href="#ico-star" x="14.53" y="13.23" width="1.54" height="1.54"/>
        <use href="#ico-star" x="30.24" y="2.54" width="2.12" height="2.12"/>
        <use href="#ico-star" x="48.46" y="-1.54" width="3.08" height="3.08"/>
        <use href="#ico-star" x="67.64" y="2.54" width="2.12" height="2.12"/>
        <use href="#ico-star" x="83.93" y="13.23" width="1.54" height="1.54"/>
      </svg>
      <div class="portrait__badge">
        <svg width="16" height="16" aria-hidden="true"><use href="#ico-pin"/></svg>
        <span><?= t('accueil.badge_photo') ?></span>
      </div>
    </div>

  </div>
</section>

<section class="section-alt">
  <div class="sky" aria-hidden="true">
    <svg width="8" height="8" style="--x:94%;--y:12.8%;--d:2.6s"><use href="#ico-star"/></svg>
    <svg width="14" height="14" style="--x:4%;--y:29.3%;--d:.5s"><use href="#ico-star"/></svg>
    <svg width="9" height="9" style="--x:97.2%;--y:45.8%;--d:1.9s"><use href="#ico-star"/></svg>
    <svg width="12" height="12" style="--x:6.5%;--y:62.4%;--d:.5s"><use href="#ico-star"/></svg>
    <svg width="9" height="9" style="--x:91.5%;--y:83.2%;--d:.3s"><use href="#ico-star"/></svg>
  </div>

  <div class="container about__grid stagger">

    <div class="about__intro reveal">
      <p class="eyebrow"><?= t('qui_suis_je.surtitre') ?></p>
      <h2><?= t('qui_suis_je.nom') ?></h2>
      <p class="about__role"><?= t('qui_suis_je.role') ?></p>
      <span class="divider-star" aria-hidden="true">
        <i></i><svg width="12" height="12"><use href="#ico-star"/></svg><i></i>
      </span>
    </div>

    <div class="reveal">
      <?php foreach (site_text('qui_suis_je.paragraphes') ?? [] as $paragraph): ?>
      <p><?= format_text($paragraph) ?></p>
      <?php endforeach ?>
      <p class="about__signature"><?= t('qui_suis_je.signature') ?></p>
    </div>

  </div>
</section>

<section id="prestations">
  <div class="sky" aria-hidden="true">
    <svg width="10" height="10" style="--x:95.8%;--y:8.3%;--d:3.9s"><use href="#ico-star"/></svg>
    <svg width="16" height="16" style="--x:1.7%;--y:21.7%;--d:.7s"><use href="#ico-star"/></svg>
    <svg width="10" height="10" style="--x:91.9%;--y:51.1%;--d:2.3s"><use href="#ico-star"/></svg>
    <svg width="15" height="15" style="--x:6.7%;--y:67.1%;--d:.9s"><use href="#ico-star"/></svg>
    <svg width="16" height="16" style="--x:96.2%;--y:85.1%;--d:3.5s"><use href="#ico-star"/></svg>
  </div>

  <div class="container">

    <header class="section-head reveal">
      <p class="eyebrow"><?= t('prestations.surtitre') ?></p>
      <h2><?= t('prestations.titre') ?></h2>
      <p class="section-head__text"><?= t('prestations.texte') ?></p>
    </header>

    <div class="cards stagger">
<?php foreach ($services as $id => $service): ?>

      <article class="card lift presta reveal">
        <svg class="presta__icon" width="52" height="52" aria-hidden="true"><use href="#<?= e($service['icon']) ?>"/></svg>
        <h3><?= format_text($service['name']) ?></h3>
        <p><?= format_text($service['text']) ?></p>
        <ul class="presta__points">
          <?php foreach ($service['points'] as $point): ?>
          <li><?= format_text($point) ?></li>
          <?php endforeach ?>
        </ul>
        <div class="presta__meta">
          <?php if ($service['available']): ?>
          <span class="tag"><?= e(duration_label($service['duration'])) ?></span>
          <span class="tag"><?= e(price_label($service)) ?></span>
          <span class="tag tag--format"><?= e($service['format']) ?></span>
          <?php else: ?>
          <span class="tag tag--soon"><?= t('prestations.a_venir') ?></span>
          <?php endif ?>
        </div>
        <?php if ($service['available']): ?>
        <a href="#rendez-vous" class="presta__link" data-service="<?= e($id) ?>"><?= t('prestations.lien_reserver') ?> <span aria-hidden="true">→</span></a>
        <?php else: ?>
        <p class="presta__soon"><?= t('prestations.bientot') ?></p>
        <?php endif ?>
      </article>
<?php endforeach ?>

    </div>
  </div>
</section>

<section class="section-soft gifts" id="bons-cadeaux">
  <div class="sky" aria-hidden="true">
    <svg width="9" height="9" style="--x:1.3%;--y:6%;--d:2.1s"><use href="#ico-star"/></svg>
    <svg width="12" height="12" style="--x:92.4%;--y:26.6%;--d:2s"><use href="#ico-star"/></svg>
    <svg width="10" height="10" style="--x:4.5%;--y:38.7%;--d:.3s"><use href="#ico-star"/></svg>
    <svg width="8" height="8" style="--x:96.7%;--y:53.4%;--d:4s"><use href="#ico-star"/></svg>
    <svg width="11" height="11" style="--x:7%;--y:75.3%;--d:2.8s"><use href="#ico-star"/></svg>
  </div>

  <div class="container gifts__grid stagger">

    <div class="gifts__intro reveal">
      <p class="eyebrow"><?= t('bons_cadeaux.surtitre') ?></p>
      <h2><?= t('bons_cadeaux.titre') ?></h2>
      <?php foreach (site_text('bons_cadeaux.paragraphes') ?? [] as $paragraph): ?>
      <p><?= format_text($paragraph) ?></p>
      <?php endforeach ?>

      <ol class="steps">
        <?php foreach (site_text('bons_cadeaux.etapes') ?? [] as $number => $step): ?>
        <li><span><?= $number + 1 ?></span><div><strong><?= format_text($step['titre']) ?></strong><p><?= format_text($step['texte']) ?></p></div></li>
        <?php endforeach ?>
      </ol>

      <a href="#demande" class="button-primary" data-prefill="bon-cadeau">
        <svg width="17" height="17" aria-hidden="true"><use href="#ico-gift"/></svg>
        <?= t('bons_cadeaux.bouton') ?>
      </a>
    </div>

    <div class="gifts__visual reveal" aria-hidden="true">
      <div class="voucher">
        <div class="voucher__frame">
          <p class="voucher__brand">L'éveil d'Elo</p>
          <span class="divider-star voucher__div">
            <i></i><svg width="10" height="10"><use href="#ico-star"/></svg><i></i>
          </span>
          <p class="voucher__label"><?= t('bons_cadeaux.image_titre') ?></p>
          <p class="voucher__value"><?= t('bons_cadeaux.image_texte') ?></p>
          <div class="voucher__foot">
            <span><?= t('bons_cadeaux.image_pour') ?> ............................</span>
            <span><?= t('bons_cadeaux.image_validite') ?></span>
          </div>
        </div>
      </div>
      <div class="voucher voucher--back"></div>
    </div>

  </div>
</section>

<section class="section-alt" id="contact">
  <div class="sky" aria-hidden="true">
    <svg width="15" height="15" style="--x:93.8%;--y:23.4%;--d:3s"><use href="#ico-star"/></svg>
    <svg width="15" height="15" style="--x:2.6%;--y:51%;--d:2.6s"><use href="#ico-star"/></svg>
    <svg width="10" height="10" style="--x:96.4%;--y:64.1%;--d:1.3s"><use href="#ico-star"/></svg>
    <svg width="15" height="15" style="--x:5.5%;--y:79.4%;--d:3.4s"><use href="#ico-star"/></svg>
  </div>

  <div class="container">

    <header class="section-head reveal">
      <p class="eyebrow"><?= t('contact.surtitre') ?></p>
      <h2><?= t('contact.titre') ?></h2>
      <p class="section-head__text"><?= t('contact.texte') ?></p>
    </header>

    <div class="contact__cards stagger">
      <a class="card lift contact__card reveal" href="mailto:<?= e($site['email']) ?>">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-mail"/></svg>
        <p><?= e($site['email']) ?></p>
        <span class="contact__cta"><?= t('contact.email_texte') ?></span>
      </a>

      <a class="card lift contact__card reveal" href="tel:<?= e($site['phone']) ?>">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-phone"/></svg>
        <p><?= e($site['phone_display']) ?></p>
        <span class="contact__cta"><?= t('contact.telephone_texte') ?></span>
      </a>

      <a class="card lift contact__card reveal" href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-insta"/></svg>
        <p><?= t('contact.instagram_titre') ?></p>
        <span class="contact__cta"><?= t('contact.instagram_texte') ?></span>
      </a>
    </div>
  </div>
</section>

<section class="booking" id="rendez-vous">
  <div class="sky" aria-hidden="true">
    <svg width="10" height="10" style="--x:2.3%;--y:7.1%;--d:3.6s"><use href="#ico-star"/></svg>
    <svg width="8" height="8" style="--x:93.5%;--y:28.4%;--d:.8s"><use href="#ico-star"/></svg>
    <svg width="9" height="9" style="--x:5.1%;--y:51.1%;--d:1.5s"><use href="#ico-star"/></svg>
    <svg width="14" height="14" style="--x:97.8%;--y:86.1%;--d:3.6s"><use href="#ico-star"/></svg>
  </div>

  <div class="container">

    <header class="section-head reveal">
      <p class="eyebrow"><?= t('rendez_vous.surtitre') ?></p>
      <h2><?= t('rendez_vous.titre') ?></h2>
      <p class="section-head__text"><?= t('rendez_vous.texte') ?></p>
    </header>

    <div class="booking__grid stagger">

      <div class="card booking__online reveal">
        <h3><?= t('rendez_vous.en_ligne_titre') ?></h3>
        <p><?= t('rendez_vous.en_ligne_texte') ?></p>

        <form class="form booking-flow" id="bookingForm" action="api/booking.php" method="post" novalidate>
          <?= $antispam ?>
          <input type="hidden" name="date">
          <input type="hidden" name="time">

          <fieldset class="field field--choices">
            <legend><span class="booking__step">1</span><?= t('rendez_vous.etape_prestation') ?></legend>
            <div class="choices">
              <?php foreach ($services as $id => $service): if (!bookable_service($id)) continue ?>
              <label><input type="radio" name="service" value="<?= e($id) ?>" data-label="<?= e($service['name']) ?>" data-duration="<?= e($service['duration']) ?>" required><span><?= e($service['name']) ?> &middot; <?= e(duration_label($service['duration'])) ?></span></label>
              <?php endforeach ?>
            </div>
          </fieldset>

          <div class="field" id="bookingWhen" hidden>
            <span><span class="booking__step">2</span><?= t('rendez_vous.etape_date') ?></span>
            <div class="calendar" id="calendar" aria-busy="false">
              <div class="calendar__head">
                <button type="button" class="calendar__nav" data-step="-1" aria-label="Mois précédent">‹</button>
                <p class="calendar__title" id="calendarTitle" aria-live="polite"></p>
                <button type="button" class="calendar__nav" data-step="1" aria-label="Mois suivant">›</button>
              </div>
              <div class="calendar__grid" id="calendarGrid"></div>
            </div>
            <div class="slots" id="slots" role="group" aria-label="Heures disponibles"></div>
          </div>

          <div class="booking__details" id="bookingDetails" hidden>
            <p class="booking__recap" id="bookingRecap"></p>

            <?php require __DIR__ . '/app/partials/person-fields.php' ?>

            <label class="field">
              <span><?= t('rendez_vous.champ_message') ?></span>
              <textarea name="message" rows="3" placeholder="<?= e(site_text('rendez_vous.champ_message_exemple')) ?>"></textarea>
            </label>

            <label class="consent">
              <input type="checkbox" name="consent" required>
              <span><?= t('rendez_vous.accord') ?> (<a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener"><?= t('formulaire.lien_confidentialite') ?></a>).</span>
            </label>

            <button type="submit" class="button-primary form__submit"><?= t('rendez_vous.bouton') ?></button>
          </div>

          <p class="form__status" id="bookingStatus" role="status" aria-live="polite"></p>
        </form>

        <div class="booking__done" id="bookingDone" tabindex="-1" hidden>
          <span class="divider-star" aria-hidden="true">
            <i></i><svg width="12" height="12"><use href="#ico-star"/></svg><i></i>
          </span>
          <p class="booking__done-title"><?= t('rendez_vous.confirme_titre') ?></p>
          <p id="bookingDoneText"></p>
          <button type="button" class="button-secondary" id="bookingAgain"><?= t('rendez_vous.bouton_autre') ?></button>
        </div>
      </div>

      <div class="booking__or" aria-hidden="true"><span><?= t('rendez_vous.ou') ?></span></div>

      <div class="card booking__form-wrap reveal" id="demande">
        <h3><?= t('rendez_vous.demande_titre') ?></h3>
        <p><?= t('rendez_vous.demande_texte') ?></p>

        <form class="form" id="rdvForm" action="api/contact.php" method="post" novalidate>
          <?= $antispam ?>

          <?php require __DIR__ . '/app/partials/person-fields.php' ?>

          <fieldset class="field field--choices">
            <legend><?= t('rendez_vous.demande_choix') ?></legend>
            <div class="choices">
              <?php foreach ($services as $service): ?>
              <label><input type="checkbox" name="prestation[]" value="<?= e($service['name']) ?>"<?= $service['available'] ? ' data-format="' . e($service['format']) . '"' : ' disabled' ?>><span><?= e($service['name']) ?></span></label>
              <?php endforeach ?>
              <label><input type="checkbox" name="prestation[]" value="<?= e($gift) ?>" data-format="<?= e(request_choices()[$gift] ?? '') ?>" id="chk-bon-cadeau"><span><?= e($gift) ?></span></label>
            </div>
          </fieldset>

          <label class="field">
            <span><?= t('rendez_vous.demande_format') ?></span>
            <input type="text" name="format" id="formatAuto" readonly tabindex="-1"
                   value="<?= e(site_text('rendez_vous.demande_format_vide')) ?>">
          </label>

          <label class="field">
            <span><?= t('rendez_vous.demande_message') ?></span>
            <textarea name="message" rows="5" placeholder="<?= e(site_text('rendez_vous.demande_message_exemple')) ?>"></textarea>
          </label>

          <label class="consent">
            <input type="checkbox" name="consent" required>
            <span><?= t('rendez_vous.demande_accord') ?> (<a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener"><?= t('formulaire.lien_confidentialite') ?></a>).</span>
          </label>

          <button type="submit" class="button-primary form__submit"><?= t('rendez_vous.demande_bouton') ?></button>
          <p class="form__status <?= $requestState ?>" id="formStatus" role="status" aria-live="polite"><?= e($requestMessage) ?></p>
        </form>
      </div>

    </div>
  </div>
</section>

</main>

<footer class="footer">
  <div class="container footer__top">
    <div class="footer__brand">
      <span class="footer__medallion">
        <span class="mark footer__mark" role="img" aria-label="Logo L'éveil d'Elo"></span>
      </span>
      <p class="footer__name">L'éveil d'Elo</p>
      <span class="divider-star footer__rule" aria-hidden="true">
        <i></i><svg width="10" height="10"><use href="#ico-star"/></svg><i></i>
      </span>
    </div>

    <nav class="footer__nav" aria-label="Navigation de pied de page">
      <a href="#accueil"><?= t('menu.accueil') ?></a>
      <a href="#prestations"><?= t('menu.prestations') ?></a>
      <a href="#bons-cadeaux"><?= t('menu.bons_cadeaux') ?></a>
      <a href="#contact"><?= t('menu.contact') ?></a>
      <a href="#rendez-vous"><?= t('menu.rendez_vous') ?></a>
    </nav>

    <div class="footer__social">
      <a href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram">
        <svg width="20" height="20" aria-hidden="true"><use href="#ico-insta"/></svg>
      </a>
      <a href="mailto:<?= e($site['email']) ?>" aria-label="E-mail">
        <svg width="20" height="20" aria-hidden="true"><use href="#ico-mail"/></svg>
      </a>
    </div>
  </div>

  <div class="container footer__bottom">
    <p>&copy; <?= date('Y') ?> L'éveil d'Elo - <?= t('pied_de_page.droits') ?></p>
    <p class="footer__legal">
      <a href="mentions-legales.php#impressum"><?= t('pied_de_page.mentions_legales') ?></a>
      <span aria-hidden="true">&middot;</span>
      <a href="mentions-legales.php#confidentialite"><?= t('pied_de_page.confidentialite') ?></a>
    </p>
    <p class="footer__disclaimer"><?= t('pied_de_page.avertissement') ?></p>
  </div>
</footer>

<a href="#accueil" class="to-top" id="toTop" aria-label="Remonter en haut">
  <svg width="20" height="20" aria-hidden="true"><use href="#ico-arrow-up"/></svg>
</a>

<script type="application/json" id="messages"><?= json_encode(site_text('messages') ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>
