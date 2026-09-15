<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$services = config('services');
$prices = array_column(array_filter($services, fn(array $service) => $service['available']), 'price');
// Titre sous ~60 caractères (au-delà, Google le coupe) et description sous ~155.
$title = "Tirage de cartes, pendule et coaching spirituel | L'éveil d'Elo";
$description = 'Tirage de cartes et pendule par téléphone, coaching spirituel en visio. Un accompagnement doux et sans jugement en Suisse romande. Réservation en ligne.';

$antispam = '<input type="hidden" name="jeton" value="' . e(form_token()) . '">'
    . '<label class="honeypot" aria-hidden="true">Site web <input type="text" name="site_web" tabindex="-1" autocomplete="off"></label>';

// Retour du formulaire de demande quand il a été envoyé sans JavaScript.
[$requestState, $requestMessage] = match ($_GET['demande'] ?? '') {
    'ok'     => ['is-ok', REQUEST_SENT],
    'erreur' => ['is-error', "L'envoi a échoué. Vous pouvez m'écrire directement par e-mail ou par téléphone."],
    default  => ['', ''],
};

$weekdays = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$schema = [
    '@context'           => 'https://schema.org',
    '@type'              => 'ProfessionalService',
    'name'               => $site['name'],
    'description'        => 'Tirage de cartes et pendule par téléphone, coaching spirituel en visio. Accompagnement doux, intuitif et sans jugement, partout en Suisse romande.',
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
<meta property="og:title" content="L'éveil d'Elo · Tirage de cartes, pendule et coaching spirituel">
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
        <li><a href="#accueil"      class="nav__link is-active">Accueil</a></li>
        <li><a href="#prestations"  class="nav__link">Prestations</a></li>
        <li><a href="#bons-cadeaux" class="nav__link">Bons cadeaux</a></li>
        <li><a href="#contact"      class="nav__link">Contact</a></li>
        <li><a href="#rendez-vous"  class="nav__link nav__link--cta"><svg width="11" height="11" aria-hidden="true"><use href="#ico-star"/></svg>Rendez-vous</a></li>
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
      <p class="eyebrow">Tirage de cartes &middot; Pendule &middot; Coaching spirituel</p>
      <h1>Écoutez ce qui<br><em>s'éveille</em> en vous</h1>
      <p class="lead">
        Je vous accueille à distance, dans un espace doux
        et sans jugement, où l'on prend le temps de déposer les questions qui pèsent
        et d'écouter les réponses qui, souvent, sont déjà là.
      </p>
      <div class="hero__actions">
        <a href="#rendez-vous" class="button-primary">Prendre rendez-vous</a>
        <a href="#prestations" class="button-secondary">Découvrir les prestations</a>
      </div>

      <ul class="hero__values">
        <li>À l'écoute</li>
        <li>Guidée par l'intuition</li>
        <li>En toute confidentialité</li>
      </ul>
    </div>

    <div class="hero__media reveal">
      <figure class="portrait">
        <img src="images/test-pp.jpg" alt="Elodie Fauquex, coach en spiritualité" class="portrait__img" fetchpriority="high">
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
        <span>À distance & en visio</span>
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
      <p class="eyebrow">Qui suis-je</p>
      <h2>Elodie</h2>
      <p class="about__role">Coach en spiritualité</p>
      <span class="divider-star" aria-hidden="true">
        <i></i><svg width="12" height="12"><use href="#ico-star"/></svg><i></i>
      </span>
    </div>

    <div class="reveal">
      <p>
        Depuis toujours, je ressens ce qui ne se dit pas. Pendant longtemps j'ai mis
        cette sensibilité de côté, jusqu'au jour où elle s'est imposée à moi comme une
        évidence : elle n'était pas un poids, mais un outil.
      </p>
      <p>
        Je me suis alors formée au tirage de cartes et au travail au pendule, et j'ai
        appris, séance après séance, à mettre cette écoute au service des autres.
        <strong>L'éveil d'Elo</strong> est né de ce cheminement.
      </p>
      <p>
        Mon rôle n'est pas de décider à votre place ni de prédire un avenir figé. Il est
        de vous offrir un miroir bienveillant, d'éclairer ce qui est encore flou et de
        vous rendre votre pouvoir de choisir.
      </p>
      <p class="about__signature">Au plaisir de vous rencontrer, Elodie</p>
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
      <p class="eyebrow">Ce que je propose</p>
      <h2>Prestations</h2>
      <p class="section-head__text">
        Chaque accompagnement est unique et s'adapte à ce que vous traversez.
        Si vous hésitez entre deux formules, écrivez-moi : nous choisirons ensemble.
      </p>
    </header>

    <div class="cards stagger">
<?php foreach ($services as $id => $service): ?>

      <article class="card lift presta reveal">
        <svg class="presta__icon" width="52" height="52" aria-hidden="true"><use href="#<?= e($service['icon']) ?>"/></svg>
        <h3><?= e($service['name']) ?></h3>
        <p><?= e($service['text']) ?></p>
        <ul class="presta__points">
          <?php foreach ($service['points'] as $point): ?>
          <li><?= e($point) ?></li>
          <?php endforeach ?>
        </ul>
        <div class="presta__meta">
          <?php if ($service['available']): ?>
          <span class="tag"><?= e(duration_label($service['duration'])) ?></span>
          <span class="tag"><?= e(price_label($service)) ?></span>
          <span class="tag tag--format"><?= e($service['format']) ?></span>
          <?php else: ?>
          <span class="tag tag--soon">À venir</span>
          <?php endif ?>
        </div>
        <?php if ($service['available']): ?>
        <a href="#rendez-vous" class="presta__link" data-service="<?= e($id) ?>">Réserver <span aria-hidden="true">→</span></a>
        <?php else: ?>
        <p class="presta__soon">Cette prestation sera bientôt disponible.</p>
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
      <p class="eyebrow">Faire plaisir</p>
      <h2>Bons cadeaux</h2>
      <p>
        Offrir un bon cadeau, c'est offrir une parenthèse : un moment rien qu'à soi,
        pour souffler et y voir plus clair.
      </p>
      <p>
        Valable sur toutes les prestations, pendant
        12 mois.
      </p>

      <ol class="steps">
        <li><span>1</span><div><strong>Vous choisissez</strong><p>Une prestation précise ou un montant libre.</p></div></li>
        <li><span>2</span><div><strong>Je crée le bon</strong><p>Personnalisé avec le prénom et votre petit mot.</p></div></li>
        <li><span>3</span><div><strong>Vous l'offrez</strong><p>Reçu par e-mail en PDF, ou imprimé sur beau papier.</p></div></li>
      </ol>

      <a href="#demande" class="button-primary" data-prefill="bon-cadeau">
        <svg width="17" height="17" aria-hidden="true"><use href="#ico-gift"/></svg>
        Commander un bon cadeau
      </a>
    </div>

    <div class="gifts__visual reveal" aria-hidden="true">
      <div class="voucher">
        <div class="voucher__frame">
          <p class="voucher__brand">L'éveil d'Elo</p>
          <span class="divider-star voucher__div">
            <i></i><svg width="10" height="10"><use href="#ico-star"/></svg><i></i>
          </span>
          <p class="voucher__label">Bon cadeau</p>
          <p class="voucher__value">Une séance au choix</p>
          <div class="voucher__foot">
            <span>Pour&nbsp;: ............................</span>
            <span>Valable 12 mois</span>
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
      <p class="eyebrow">Parlons-en</p>
      <h2>Contact</h2>
      <p class="section-head__text">
        Une question avant de réserver&nbsp;? Un doute sur la prestation qui vous
        correspond&nbsp;? Écrivez-moi, je réponds sous 48&nbsp;h.
      </p>
    </header>

    <div class="contact__cards stagger">
      <a class="card lift contact__card reveal" href="mailto:<?= e($site['email']) ?>">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-mail"/></svg>
        <p><?= e($site['email']) ?></p>
        <span class="contact__cta">Écrire un message</span>
      </a>

      <a class="card lift contact__card reveal" href="tel:<?= e($site['phone']) ?>">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-phone"/></svg>
        <p><?= e($site['phone_display']) ?></p>
        <span class="contact__cta"><?= e(opening_label()) ?></span>
      </a>

      <a class="card lift contact__card reveal" href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener">
        <svg width="26" height="26" aria-hidden="true"><use href="#ico-insta"/></svg>
        <p>Instagram</p>
        <span class="contact__cta">Tirages du mois &amp; guidances</span>
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
      <p class="eyebrow">Réserver</p>
      <h2>Prendre rendez-vous</h2>
      <p class="section-head__text">
        Deux façons de convenir d'un moment ensemble : choisissez celle qui vous ressemble le plus.
      </p>
    </header>

    <div class="booking__grid stagger">

      <div class="card booking__online reveal">
        <h3>Réserver en ligne</h3>
        <p>
          Choisissez directement un créneau libre dans mon agenda. La confirmation vous
          parvient aussitôt par e-mail.
        </p>

        <form class="form booking-flow" id="bookingForm" action="api/booking.php" method="post" novalidate>
          <?= $antispam ?>
          <input type="hidden" name="date">
          <input type="hidden" name="time">

          <fieldset class="field field--choices">
            <legend><span class="booking__step">1</span>La prestation</legend>
            <div class="choices">
              <?php foreach ($services as $id => $service): if (!bookable_service($id)) continue ?>
              <label><input type="radio" name="service" value="<?= e($id) ?>" data-label="<?= e($service['name']) ?>" data-duration="<?= e($service['duration']) ?>" required><span><?= e($service['name']) ?> &middot; <?= e(duration_label($service['duration'])) ?></span></label>
              <?php endforeach ?>
            </div>
          </fieldset>

          <div class="field" id="bookingWhen" hidden>
            <span><span class="booking__step">2</span>Le jour et l'heure</span>
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
              <span>Un mot avant la séance</span>
              <textarea name="message" rows="3" placeholder="Facultatif"></textarea>
            </label>

            <label class="consent">
              <input type="checkbox" name="consent" required>
              <span>J'accepte que ces informations soient enregistrées dans l'agenda pour organiser le rendez-vous (<a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>).</span>
            </label>

            <button type="submit" class="button-primary form__submit">Confirmer le rendez-vous</button>
          </div>

          <p class="form__status" id="bookingStatus" role="status" aria-live="polite"></p>
        </form>

        <div class="booking__done" id="bookingDone" tabindex="-1" hidden>
          <span class="divider-star" aria-hidden="true">
            <i></i><svg width="12" height="12"><use href="#ico-star"/></svg><i></i>
          </span>
          <p class="booking__done-title">C'est noté&nbsp;!</p>
          <p id="bookingDoneText"></p>
          <button type="button" class="button-secondary" id="bookingAgain">Réserver un autre moment</button>
        </div>
      </div>

      <div class="booking__or" aria-hidden="true"><span>ou</span></div>

      <div class="card booking__form-wrap reveal" id="demande">
        <h3>Faire une demande</h3>
        <p>Dites-moi ce qui vous amène, je vous propose un créneau par retour de message.</p>

        <form class="form" id="rdvForm" action="api/contact.php" method="post" novalidate>
          <?= $antispam ?>

          <?php require __DIR__ . '/app/partials/person-fields.php' ?>

          <fieldset class="field field--choices">
            <legend>Votre demande</legend>
            <div class="choices">
              <?php foreach ($services as $service): ?>
              <label><input type="checkbox" name="prestation[]" value="<?= e($service['name']) ?>"<?= $service['available'] ? ' data-format="' . e($service['format']) . '"' : ' disabled' ?>><span><?= e($service['name']) ?></span></label>
              <?php endforeach ?>
              <label><input type="checkbox" name="prestation[]" value="Bon cadeau" data-format="<?= e(request_choices()['Bon cadeau']) ?>" id="chk-bon-cadeau"><span>Bon cadeau</span></label>
            </div>
          </fieldset>

          <label class="field">
            <span>Format</span>
            <input type="text" name="format" id="formatAuto" readonly tabindex="-1"
                   value="Selon la prestation choisie">
          </label>

          <label class="field">
            <span>Votre message</span>
            <textarea name="message" rows="5" placeholder="Ce qui vous amène, une question…"></textarea>
          </label>

          <label class="consent">
            <input type="checkbox" name="consent" required>
            <span>J'accepte que ces informations soient utilisées pour me recontacter (<a href="mentions-legales.php#confidentialite" target="_blank" rel="noopener">politique de confidentialité</a>).</span>
          </label>

          <button type="submit" class="button-primary form__submit">Envoyer ma demande</button>
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
      <a href="#accueil">Accueil</a>
      <a href="#prestations">Prestations</a>
      <a href="#bons-cadeaux">Bons cadeaux</a>
      <a href="#contact">Contact</a>
      <a href="#rendez-vous">Rendez-vous</a>
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
    <p>&copy; <?= date('Y') ?> L'éveil d'Elo - Tous droits réservés</p>
    <p class="footer__legal">
      <a href="mentions-legales.php#impressum">Mentions légales</a>
      <span aria-hidden="true">&middot;</span>
      <a href="mentions-legales.php#confidentialite">Politique de confidentialité</a>
    </p>
    <p class="footer__disclaimer">
      Les séances proposées relèvent du bien-être et ne remplacent en aucun cas un avis
      ou un suivi médical.
    </p>
  </div>
</footer>

<a href="#accueil" class="to-top" id="toTop" aria-label="Remonter en haut">
  <svg width="20" height="20" aria-hidden="true"><use href="#ico-arrow-up"/></svg>
</a>

<script src="<?= e(asset('js/script.js')) ?>"></script>
</body>
</html>
