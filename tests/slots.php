<?php
// Tests du calcul des créneaux et des textes du site : php tests/slots.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

$tz = new DateTimeZone('Europe/Zurich');
$at = fn(string $time) => (new DateTimeImmutable($time, $tz))->getTimestamp();
$day = fn(string $date) => new DateTimeImmutable($date, $tz);
// Réglages fixes : les tests ne dépendent pas des valeurs choisies dans config.php.
$rules = [
    'availability_keyword' => '',
    'interval'             => 15,
    'buffer'               => 15,
    'min_notice'           => 24,
    'max_days'             => 60,
    'hours'                => array_fill(1, 6, [['09:00', '19:00']]) + [7 => []],
] + config('booking');
$failures = 0;
$check = function (string $label, mixed $actual, mixed $expected) use (&$failures) {
    if ($actual === $expected) {
        echo "OK    $label\n";
        return;
    }
    $failures++;
    echo "ÉCHEC $label\n      attendu : " . json_encode($expected) . "\n      obtenu  : " . json_encode($actual) . "\n";
};
$event = fn(string $start, string $end, bool $free = false, string $summary = 'Occupé', bool $site = false, string $id = '') =>
    ['id' => $id, 'summary' => $summary, 'start' => $at($start), 'end' => $at($end), 'free' => $free, 'site' => $site];
$summary = fn(array $slots) => [$slots[0] ?? null, end($slots) ?: null, count($slots)];

$now = $at('2026-09-14 08:00');
$wed = $day('2026-09-16');
$slotsOn = fn(int $duration, array $events = [], ?int $when = null, ?array $with = null) =>
    available_slots($duration, $events, $wed, $wed->modify('+1 day'), $when ?? $now, $with ?? $rules)['2026-09-16'] ?? [];

$check('journée 9h-19h, tous les quarts d\'heure : séances de 45, 60 et 75 min',
    [$summary($slotsOn(45)), $summary($slotsOn(60)), $summary($slotsOn(75))],
    [['09:00', '18:15', 38], ['09:00', '18:00', 37], ['09:00', '17:45', 36]]);
$check('occupé 12h-13h + 15 min de pause : dernier départ 10h45, reprise 13h15',
    array_values(array_intersect($slotsOn(60, [$event('2026-09-16 12:00', '2026-09-16 13:00')]), ['10:45', '11:00', '13:00', '13:15'])), ['10:45', '13:15']);
$check('pause de 10 min : reprise au quart d\'heure suivant (13h15)',
    array_values(array_intersect($slotsOn(60, [$event('2026-09-16 12:00', '2026-09-16 13:00')], null, ['buffer' => 10] + $rules), ['13:00', '13:15'])), ['13:15']);
$check('événement marqué « disponible » ignoré', count($slotsOn(60, [$event('2026-09-16 12:00', '2026-09-16 13:00', true)])), 37);
$check('journée entière occupée', available_slots(60, [$event('2026-09-16 00:00', '2026-09-17 00:00')], $wed, $wed->modify('+1 day'), $now, $rules), []);
$check('dimanche fermé', available_slots(60, [], $day('2026-09-20'), $day('2026-09-21'), $now, $rules), []);
$check('préavis de 24 h : premier quart d\'heure après 10h10 le lendemain', $slotsOn(60, [], $at('2026-09-15 10:10'))[0], '10:15');
$check('préavis de 2 h : mercredi 10h03, premier créneau à 12h15', $slotsOn(45, [], $at('2026-09-16 10:03'), ['min_notice' => 2] + $rules)[0] ?? null, '12:15');

$result = available_slots(60, [], $day('2026-09-01'), $day('2026-12-01'), $now, $rules);
$check('horizon de 60 jours', [array_key_last($result), end($result[array_key_last($result)])], ['2026-11-13', '18:00']);

$month = available_slots(75, [$event('2026-09-22 09:00', '2026-09-22 19:00')], $day('2026-09-01'), $day('2026-10-01'), $now, $rules);
$check('mois : jours passés exclus', array_key_first($month), '2026-09-15');
$check('mois : mardi 22 bloqué', isset($month['2026-09-22']), false);

$keyword = ['availability_keyword' => 'dispo'] + $rules;
$window = $event('2026-09-16 14:00', '2026-09-16 17:00', true, 'Dispo');
$check('mode « Dispo » : seules les plages créées ouvrent',
    $slotsOn(60, [$window, $event('2026-09-16 16:00', '2026-09-16 16:30')], null, $keyword), ['14:00', '14:15', '14:30', '14:45']);
$check('mode « Dispo » : un client nommé « Dispo » reste un rendez-vous occupé',
    $slotsOn(60, [$window, $event('2026-09-16 14:00', '2026-09-16 14:45', false, 'Pendule · Dispo Dupont', true)], null, $keyword), ['15:00', '15:15', '15:30', '15:45', '16:00']);
$check('mode « Dispo » : un rendez-vous du site (titre retiré du cache) reste occupé',
    $slotsOn(60, [$event('2026-09-16 14:00', '2026-09-16 16:00', false, 'Dispo'), $event('2026-09-16 14:00', '2026-09-16 15:00', false, '', true)], null, $keyword), []);
$check('mode « Dispo » : un événement sans titre ouvre aussi une plage',
    $slotsOn(60, [$event('2026-09-16 14:00', '2026-09-16 16:00', false, '')], null, $keyword), ['14:00', '14:15', '14:30', '14:45', '15:00']);
$check('mode « Dispo » : plage 18h-19h juste après un tirage de 17h-18h → pendule à 18h15',
    $slotsOn(45, [$event('2026-09-16 18:00', '2026-09-16 19:00', false, 'Dispo'), $event('2026-09-16 17:00', '2026-09-16 18:00', false, 'Tirage', true)], null, $keyword), ['18:15']);

$slots = available_slots(60, [$event('2026-10-25 09:00', '2026-10-25 12:00', false, 'DISPO dimanche')], $day('2026-10-25'), $day('2026-10-26'), $now, $keyword)['2026-10-25'] ?? [];
$check('mode « Dispo » un jour de changement d\'heure', $summary($slots), ['09:00', '11:00', 9]);

$dispo = [$event('2026-09-16 17:00', '2026-09-16 19:00', false, 'Dispo', false, 'd1'), $event('2026-09-16 17:00', '2026-09-16 19:00', false, 'Médecin', false, 'm1')];
$check('plage 17h-19h, rendez-vous 17h-18h : la plage devient 18h15-19h (pause comprise)',
    availability_changes($dispo, $at('2026-09-16 17:00'), $at('2026-09-16 18:00'), $keyword), [['update', 'd1', ['start' => $at('2026-09-16 18:15')]]]);
$check('plage 17h-19h, rendez-vous 17h30-18h15 : la plage est coupée en deux, pauses comprises',
    availability_changes($dispo, $at('2026-09-16 17:30'), $at('2026-09-16 18:15'), $keyword), [
        ['update', 'd1', ['end' => $at('2026-09-16 17:15')]],
        ['create', null, ['summary' => 'Dispo', 'start' => $at('2026-09-16 18:30'), 'end' => $at('2026-09-16 19:00'), 'transparency' => 'opaque']],
    ]);
$check('plage 17h-19h, rendez-vous 17h-18h45 : la plage est supprimée',
    availability_changes($dispo, $at('2026-09-16 17:00'), $at('2026-09-16 18:45'), $keyword), [['delete', 'd1', []]]);
$check('plage 16h-17h collée à un rendez-vous de 17h : elle finit à 16h45',
    availability_changes([$event('2026-09-16 16:00', '2026-09-16 17:00', false, 'Dispo', false, 'd2')], $at('2026-09-16 17:00'), $at('2026-09-16 17:45'), $keyword),
    [['update', 'd2', ['end' => $at('2026-09-16 16:45')]]]);
$check('heures fixes : aucune plage « Dispo » modifiée', availability_changes($dispo, $at('2026-09-16 17:00'), $at('2026-09-16 18:00'), $rules), []);

// Réservation puis annulation, rejouées sur une copie de l'agenda : les plages « Dispo » doivent revenir comme avant.
$apply = function (array $events, array $changes): array {
    foreach ($changes as $number => [$action, $id, $fields]) {
        $index = array_search($id, array_column($events, 'id'), true);
        match ($action) {
            'update' => $events[$index] = array_replace($events[$index], $fields),
            'delete' => array_splice($events, $index, 1),
            'create' => $events[] = ['id' => "nouvelle$number", 'summary' => $fields['summary'], 'start' => $fields['start'], 'end' => $fields['end'],
                'free' => $fields['transparency'] === 'transparent', 'site' => false],
        };
        $events = array_values($events);
    }
    return $events;
};
$windows = function (array $events) use ($keyword): array {
    $list = array_map(fn(array $event) => date('H:i', $event['start']) . '-' . date('H:i', $event['end']), array_filter($events, fn(array $event) => is_availability($event, $keyword)));
    sort($list);
    return $list;
};
// Renvoie l'agenda avec le rendez-vous, et ce qu'il garde pour son annulation (id et morceaux retirés).
$book = function (array $events, string $start, string $end) use ($apply, $at, $keyword): array {
    [$start, $end] = [$at("2026-09-16 $start"), $at("2026-09-16 $end")];
    $id = "rdv$start";
    $carved = carved_availability($events, $start, $end, $keyword);
    $events = $apply($events, availability_changes($events, $start, $end, $keyword));
    $events[] = ['id' => $id, 'summary' => '', 'start' => $start, 'end' => $end, 'free' => false, 'site' => true];
    return [$events, [$id, $carved]];
};
$cancel = function (array $events, array $booking) use ($apply, $keyword): array {
    [$id, $carved] = $booking;
    $events = array_values(array_filter($events, fn(array $event) => $event['id'] !== $id));
    return $apply($events, restored_availability($events, $carved, $keyword));
};

$plage = [$event('2026-09-16 17:00', '2026-09-16 19:00', false, 'Dispo', false, 'd1')];
foreach (['au milieu (plage coupée en deux)' => ['17:30', '18:15'], 'au début (plage raccourcie)' => ['17:00', '18:00'],
          'à la fin (plage raccourcie)' => ['18:00', '19:00'], 'sur toute la plage (plage supprimée)' => ['17:00', '18:45']] as $label => [$from, $to]) {
    [$booked, $booking] = $book($plage, $from, $to);
    $check("annulation d'un rendez-vous $label : plage 17h-19h rendue", $windows($cancel($booked, $booking)), ['17:00-19:00']);
}

[$first, $bookingFirst] = $book($plage, '17:00', '17:45');
[$both, $bookingSecond] = $book($first, '18:00', '18:30');
$check('deux rendez-vous : plage restante 18h45-19h', $windows($both), ['18:45-19:00']);
// La pause d'avant le second rendez-vous (17h45-18h) reste dans la plage rendue : le site la compte quand même.
$check('deux rendez-vous, le premier annulé : plage rendue jusqu\'au second', $windows($cancel($both, $bookingFirst)), ['17:00-18:00', '18:45-19:00']);
$check('deux rendez-vous, le premier annulé : le second garde sa pause',
    available_slots(15, $cancel($both, $bookingFirst), $wed, $wed->modify('+1 day'), $now, $keyword)['2026-09-16'] ?? [], ['17:00', '17:15', '17:30', '18:45']);
$check('deux rendez-vous annulés, dans un ordre ou dans l\'autre : plage entière',
    [$windows($cancel($cancel($both, $bookingFirst), $bookingSecond)), $windows($cancel($cancel($both, $bookingSecond), $bookingFirst))], [['17:00-19:00'], ['17:00-19:00']]);

[$booked, [, $carved]] = $book($plage, '17:30', '18:15');
$check('plages autour supprimées par Elodie depuis : rien de recréé', restored_availability([], $carved, $keyword), []);
$check('morceau déjà recouvert par une plage recréée à la main : rien de changé',
    restored_availability([$event('2026-09-16 17:00', '2026-09-16 19:00', false, 'Dispo', false, 'd9')], $carved, $keyword), []);
$check('rendez-vous hors de toute plage : rien à rendre', carved_availability($plage, $at('2026-09-16 10:00'), $at('2026-09-16 11:00'), $keyword), []);

$check('lien d\'annulation : signature de 16 caractères, propre à chaque rendez-vous',
    [strlen(cancel_signature('abc123')), cancel_signature('abc123') === cancel_signature('abc123'), cancel_signature('abc123') === cancel_signature('abc124')],
    [16, true, false]);
$check('lien d\'annulation : adresse courte', strlen(cancel_url('k3v9q2m1f8a7d6c5b4e3n2p1o0')) < 100, true);
$appointment = site_appointment(['start' => ['dateTime' => '2026-09-16T17:00:00+02:00'], 'end' => ['dateTime' => '2026-09-16T18:00:00+02:00'],
    'extendedProperties' => ['private' => ['source' => 'site', 'prestation' => 'Tirage', 'prenom' => 'Léa', 'nom' => 'M', 'email' => 'lea@x.ch', 'telephone' => '079', 'dispo' => json_encode($carved)]]]);
$check('rendez-vous relu depuis Google : heures et plage à rendre',
    [period_fr($appointment['start'], $appointment['end']), $appointment['dispo']], ['mercredi 16 septembre 2026, de 17h00 à 18h00', $carved]);
$check('événement ajouté à la main : pas annulable par un lien', site_appointment(['start' => ['dateTime' => '2026-09-16T17:00:00+02:00'], 'summary' => 'Dispo']), null);

$check('libellés', [duration_label(45), duration_label(75), date_fr($day('2026-09-16 09:00'))], ['45 min', '1 h 15', 'mercredi 16 septembre 2026 à 9h00']);

$site = require dirname(__DIR__) . '/textes/site-text.php';
$check('site-text.php : toutes les sections',
    array_values(array_diff(['menu', 'accueil', 'qui_suis_je', 'prestations', 'bons_cadeaux', 'contact', 'rendez_vous', 'formulaire', 'pied_de_page', 'annulation', 'messages', 'google'], array_keys($site))), []);
$check('site-text.php : tous les textes de la page d\'annulation',
    array_values(array_diff(['google_titre', 'surtitre', 'titre', 'confirmer', 'bouton', 'garder', 'annule_titre', 'annule', 'bouton_autre',
        'trop_tard', 'passe', 'deja_annule', 'invalide', 'erreur', 'retour'], array_keys($site['annulation'] ?? []))), []);
foreach (array_keys(config('services')) as $id) {
    $card = $site['prestations']['cartes'][$id] ?? [];
    // Une prestation à formules n'a ni durée ni prix : chaque formule a les siens.
    $offers = $card['offres'] ?? [];
    $complete = !array_diff(['nom', 'texte', 'points', 'disponible'], array_keys($card))
        && (empty($card['disponible']) || isset($card['format']))
        && (empty($card['disponible']) || $offers || isset($card['duree'], $card['prix']))
        && !array_filter($offers, fn(array $offer) => array_diff(['nom', 'duree', 'duree_reservation', 'finalite', 'tarif'], array_keys($offer)));
    $check("site-text.php : carte « $id » complète", $complete, true);
}
$usedMessages = ['champs_obligatoires', 'envoi_en_cours', 'demande_envoyee', 'envoi_echoue', 'recherche', 'aucun_creneau', 'agenda_indisponible',
    'reservation_fermee', 'choisir_creneau', 'reservation_en_cours', 'reservation_echouee', 'creneau_pris', 'reservation_confirmee',
    'reservation_confirmee_telephone', 'email_invalide', 'nom_invalide', 'telephone_invalide', 'trop_envois', 'page_expiree'];
$check('site-text.php : tous les messages utilisés par le site', array_values(array_diff($usedMessages, array_keys($site['messages'] ?? []))), []);
$options = booking_options();
$check('réservation : chaque option bloque un nombre entier et positif de minutes',
    array_keys(array_filter($options, fn(array $option) => !is_int($option['duration']) || $option['duration'] <= 0)), []);
$check('réservation : le coaching se réserve par formule, avec son nom, sa durée et son tarif',
    array_map(fn(array $option) => [$option['name'], $option['duration'], price_label($option)], array_filter($options, fn(array $option) => $option['service'] === 'coaching')),
    array_combine(
        array_map(fn(int $index) => "coaching.$index", array_keys($site['prestations']['cartes']['coaching']['offres'])),
        array_map(fn(array $offer) => ["Coaching spirituel · {$offer['nom']}", $offer['duree_reservation'], $offer['tarif']], $site['prestations']['cartes']['coaching']['offres'])
    ));
$check('réservation : une prestation « À venir » ne se réserve pas', array_key_exists('reiki', $options), false);
$check('fiche Google : montant lu dans le prix ou le tarif',
    array_map(fn(?string $tarif) => price_amount($tarif === null ? ['price' => 45] : ['price_text' => $tarif]), [null, 'Offert', '330 CHF', "1'200 CHF", 'CHF 1 200', 'dès 90 CHF']),
    [45, null, 330, 1200, 1200, 90]);
$check('mise en forme des textes : gras, italique, espace insécable, balises neutralisées',
    format_text("**L'éveil** *s'éveille* ? <b>"), "<strong>L&#039;éveil</strong> <em>s&#039;éveille</em>\u{00A0}? &lt;b&gt;");

$legal = require dirname(__DIR__) . '/textes/mentions-legales.php';
$check('mentions-legales.php : les deux parties ont leurs rubriques',
    [count($legal['mentions']['rubriques'] ?? []) > 0, count($legal['confidentialite']['rubriques'] ?? []) > 0], [true, true]);

$mail = mail_template("Bonjour {prenom},\n\n{details}\n\nÀ bientôt,\nElodie", ['prenom' => 'Dylan'], ['Date' => 'jeudi']);
$check('e-mail modifiable : mots remplacés et récapitulatif en gras',
    [$mail['text'], str_contains($mail['html'], '<strong>Date :</strong> jeudi')],
    ["Bonjour Dylan,\n\nDate : jeudi\n\nÀ bientôt,\nElodie\n", true]);
$mail = mail_template('Pour annuler : [annuler mon rendez-vous]({lien})', ['lien' => 'https://x.ch/annuler.php?r=a&s=b']);
$check('e-mail : [texte](lien) cliquable sur le texte, adresse seule dans la version texte',
    [$mail['text'], str_contains($mail['html'], '<a href="https://x.ch/annuler.php?r=a&amp;s=b">annuler mon rendez-vous</a>')],
    ["Pour annuler : https://x.ch/annuler.php?r=a&s=b\n", true]);

$texts = require dirname(__DIR__) . '/textes/appointment-text.php';
$request = array_fill_keys(['prenom', 'nom', 'telephone', 'telephone_elodie'], 'x');
$cancelled = $request + ['prestation' => 'x', 'date' => 'x'];
$booked = $cancelled + ['tarif' => 'x', 'lien_annulation' => 'https://x.ch', 'delai_annulation' => 'x'];
$emails = ['avis_reservation' => $booked, 'demande' => $request, 'avis_demande' => $request, 'annulation' => $cancelled, 'avis_annulation' => $cancelled];
foreach (array_keys(config('services')) as $id) {
    if (bookable_service($id)) {
        $emails[$id] = $booked;
    }
}
foreach ($emails as $key => $values) {
    $rendered = fill_placeholders($texts[$key]['subject'] ?? '', $values) . mail_template($texts[$key]['body'] ?? '', $values, ['Date' => 'x'])['text'];
    $check("appointment-text.php : e-mail « $key » présent, sans {mot} inconnu", [isset($texts[$key]), str_contains($rendered, '{')], [true, false]);
}
$check('prestation sans texte : confirmation simple, avec le lien d\'annulation',
    [str_contains(appointment_text('inconnue')['body'], '{details}'), str_contains(mail_template(appointment_text('inconnue')['body'], $booked)['text'], '{')], [true, false]);

echo $failures ? "\n$failures test(s) en échec\n" : "\nTous les tests passent\n";
exit($failures ? 1 : 0);
