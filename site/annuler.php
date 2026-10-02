<?php
require __DIR__ . '/app/bootstrap.php';

// Lien de l'e-mail de confirmation : annuler?r=<rendez-vous Google>&s=<signature>.
// Ouvrir le lien n'annule rien : un logiciel de messagerie qui visite les liens ne touche pas au rendez-vous.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$site = config('site');
$param = fn(string $key): string => is_string($value = $_POST[$key] ?? $_GET[$key] ?? null) ? $value : '';
$id = $param('r');
$signature = $param('s');
$appointment = null;
$state = 'invalide';
$clientEmailSent = true;

try {
    if ($id !== '' && hash_equals(cancel_signature($id), $signature)) {
        $event = calendar_get_event($id);
        $appointment = $event ? site_appointment($event) : null;
        $left = $appointment ? $appointment['start']->getTimestamp() - time() : 0;
        $state = match (true) {
            $event === null                                   => 'deja_annule',
            $appointment === null                             => 'invalide',
            $left <= 0                                        => 'passe',
            $left < config('booking.cancel_notice') * 3600    => 'trop_tard',
            default                                           => 'confirmer',
        };
    }

    if ($state === 'confirmer' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $cancelled = with_lock('booking', function () use ($id, $appointment): bool {
            // Relu sous le verrou : un double clic n'annule et ne prévient qu'une fois.
            if (!calendar_get_event($id)) {
                return false;
            }
            // Autour du rendez-vous et des morceaux de plage à rendre, même si Elodie l'a déplacé depuis.
            $pieces = $appointment['dispo'];
            $times = [$appointment['start']->getTimestamp(), $appointment['end']->getTimestamp(), ...array_column($pieces, 'start'), ...array_column($pieces, 'end')];
            $around = events_around(new DateTimeImmutable('@' . min($times)), new DateTimeImmutable('@' . max($times)));
            $events = array_filter($around, fn(array $event) => $event['id'] !== $id);

            calendar_delete_event($id);
            try {
                apply_availability_changes(restored_availability($events, $pieces, config('booking')));
            } catch (GoogleError $e) {
                // Le rendez-vous est annulé ; seule la plage « Dispo » reste à remettre à la main.
                error_log('Plage Dispo rendue : ' . $e->getMessage());
            }
            forget_calendar_cache();
            return true;
        });
        $state = $cancelled ? 'annule' : 'deja_annule';

        if ($cancelled) {
            // Textes : textes/appointment-text.php.
            $sent = send_text_mails(cancellation_emails($appointment),
                person_values($appointment) + ['prestation' => $appointment['prestation'], 'date' => date_fr($appointment['start'])], 'Annulation');
            $clientEmailSent = $sent[0];
        }
    }
} catch (GoogleError $e) {
    error_log('Annulation : ' . $e->getMessage());
    $state = 'erreur';
}

$texts = site_text('annulation') ?? [];
$title = (string) ($texts['google_titre'] ?? '');
$description = '';
$phone = '<a href="tel:' . e($site['phone']) . '">' . e($site['phone_display']) . '</a>';
$say = fn(string $key): string => strtr(format_text(fill_placeholders((string) ($texts[$key] ?? ''), [
    'prestation' => $appointment['prestation'] ?? '',
    'date'       => $appointment ? date_fr($appointment['start']) : '',
    'delai'      => cancel_notice_label(),
])), ['{telephone}' => $phone]);
$heading = $texts[$state . '_titre'] ?? $texts['titre'] ?? '';
$needsContact = in_array($state, ['trop_tard', 'invalide', 'erreur'], true);
$isCancelled = in_array($state, ['annule', 'deja_annule'], true);
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<meta name="robots" content="noindex">
</head>
<body class="cancel-page">

<header class="cancel-page__header">
  <div class="header__inner">
    <?php [$brandHref, $brandLabel] = ['./', "retour à l'accueil"]; require __DIR__ . '/app/partials/brand.php' ?>
  </div>
</header>

<main class="cancel-page__main">
  <div class="cancel-page__content">

    <div class="cancel__heading">
      <div class="cancel__symbol" aria-hidden="true">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round">
        <?php if ($isCancelled): ?>
          <circle cx="16" cy="16" r="11"/><path d="m10.5 16 3.7 3.7 7.3-7.4"/>
        <?php elseif (in_array($state, ['trop_tard', 'passe'], true)): ?>
          <circle cx="16" cy="16" r="11"/><path d="M16 9v7l4.5 3"/>
        <?php elseif ($needsContact): ?>
          <circle cx="16" cy="16" r="11"/><path d="M16 14v8M16 10v.2"/>
        <?php else: ?>
          <rect x="5" y="7" width="22" height="21" rx="4"/><path d="M11 4v6M21 4v6M5 14h22m-15 5 8 5m0-5-8 5"/>
        <?php endif ?>
        </svg>
      </div>
      <p class="eyebrow"><?= format_text($texts['surtitre'] ?? '') ?></p>
      <h1 id="cancel-title"><?= format_text($heading) ?></h1>
    </div>

    <section class="cancel" aria-labelledby="cancel-title">
      <?php if ($appointment): ?>
      <div class="cancel__appointment">
        <div class="cancel__date" aria-hidden="true">
          <span><?= e(mb_substr(MONTHS_FR[(int) $appointment['start']->format('n')], 0, 3)) ?>.</span>
          <strong><?= e($appointment['start']->format('j')) ?></strong>
        </div>
        <div class="cancel__details">
          <p class="cancel__label"><?= format_text($texts['seance'] ?? '') ?></p>
          <h2><?= e($appointment['prestation']) ?></h2>
          <p><time datetime="<?= e($appointment['start']->format(DATE_RFC3339)) ?>"><?= e(date_fr($appointment['start'])) ?></time></p>
          <p class="cancel__timezone"><?= format_text($texts['fuseau'] ?? '') ?></p>
        </div>
      </div>
      <?php endif ?>

      <div class="cancel__body">
      <p class="cancel__message"><?= $say($state === 'annule' && !$clientEmailSent ? 'annule_sans_email' : $state) ?></p>

      <?php if ($needsContact): ?>
      <p class="cancel__contact">
        <?= format_text($texts['par_sms'] ?? '') ?> <a href="sms:<?= e($site['phone']) ?>"><?= e($site['phone_display']) ?></a>
        <?= format_text($texts['par_mail'] ?? '') ?> <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>
      </p>
      <?php endif ?>

      <?php if ($state === 'confirmer' || $isCancelled || $state === 'passe'): ?>
      <div class="cancel__actions">
      <?php if ($state === 'confirmer'): ?>
        <form method="post" action="annuler">
          <input type="hidden" name="r" value="<?= e($id) ?>">
          <input type="hidden" name="s" value="<?= e($signature) ?>">
          <button type="submit" class="button-primary"><?= format_text($texts['bouton'] ?? '') ?></button>
        </form>
        <a href="./" class="button-secondary"><?= format_text($texts['garder'] ?? '') ?></a>
      <?php elseif ($isCancelled || $state === 'passe'): ?>
        <a href="./#rendez-vous" class="button-primary"><?= format_text($texts['bouton_autre'] ?? '') ?></a>
      <?php endif ?>
      </div>
      <?php endif ?>
      </div>
    </section>

    <div class="cancel__return">
      <a href="./" class="cancel__back"><?= format_text($texts['retour'] ?? '') ?></a>
    </div>
  </div>
</main>

<footer class="cancel-page__footer">
  <div class="container">
    <?php require __DIR__ . '/app/partials/copyright.php' ?>
  </div>
</footer>

</body>
</html>
