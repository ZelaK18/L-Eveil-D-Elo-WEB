<?php
require __DIR__ . '/app/bootstrap.php';

// Lien de l'e-mail de confirmation : annuler.php?r=<rendez-vous Google>&s=<signature>.
// Ouvrir le lien n'annule rien : un logiciel de messagerie qui visite les liens ne touche pas au rendez-vous.
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$site = config('site');
$param = fn(string $key): string => is_string($value = $_POST[$key] ?? $_GET[$key] ?? null) ? $value : '';
$id = $param('r');
$signature = $param('s');
$appointment = null;
$state = 'invalide';

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
            $details = ['Prestation' => $appointment['prestation'], 'Date' => period_fr($appointment['start'], $appointment['end'])];
            send_text_mails([
                [$appointment['email'], 'annulation', null, $details],
                [config('mail_to'), 'avis_annulation', $appointment['email'], $details + [
                    'Nom'       => $appointment['nom'],
                    'Prénom'    => $appointment['prenom'],
                    'E-mail'    => $appointment['email'],
                    'Téléphone' => $appointment['telephone'],
                ]],
            ], person_values($appointment) + ['prestation' => $appointment['prestation'], 'date' => date_fr($appointment['start'])], 'Annulation');
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
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<meta name="robots" content="noindex">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <?php [$brandHref, $brandLabel] = ['./', "retour à l'accueil"]; require __DIR__ . '/app/partials/brand.php' ?>
  </div>
</header>

<main class="legal">
  <div class="container legal__inner">

    <p class="eyebrow"><?= format_text($texts['surtitre'] ?? '') ?></p>
    <h1><?= format_text($texts[$state === 'annule' ? 'annule_titre' : 'titre'] ?? '') ?></h1>

    <section class="card cancel">
      <p><?= $say($state) ?></p>

      <div class="cancel__actions">
      <?php if ($state === 'confirmer'): ?>
        <form method="post" action="annuler.php">
          <input type="hidden" name="r" value="<?= e($id) ?>">
          <input type="hidden" name="s" value="<?= e($signature) ?>">
          <button type="submit" class="button-primary"><?= format_text($texts['bouton'] ?? '') ?></button>
        </form>
        <a href="./" class="button-secondary"><?= format_text($texts['garder'] ?? '') ?></a>
      <?php elseif ($state === 'annule'): ?>
        <a href="./#rendez-vous" class="button-primary"><?= format_text($texts['bouton_autre'] ?? '') ?></a>
      <?php else: ?>
        <a href="./" class="button-secondary"><?= format_text($texts['retour'] ?? '') ?></a>
      <?php endif ?>
      </div>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container footer__bottom">
    <?php require __DIR__ . '/app/partials/copyright.php' ?>
  </div>
</footer>

</body>
</html>
