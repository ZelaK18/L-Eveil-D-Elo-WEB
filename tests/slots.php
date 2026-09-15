<?php
// Tests du calcul des créneaux et des textes de réservation : php tests/slots.php

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

$check('libellés', [duration_label(45), duration_label(75), date_fr($day('2026-09-16 09:00')), opening_label()], ['45 min', '1 h 15', 'mercredi 16 septembre 2026 à 9h00', 'Du lundi au samedi, 9h à 19h']);
$check('durées de config.php : tirage 45 min, pendule 45 min, coaching 1 h',
    array_map(fn(string $id) => bookable_service($id)['duration'], ['tirage', 'pendule', 'coaching']), [45, 45, 60]);

$mail = mail_template("Bonjour {prenom},\n\n{details}\n\nÀ bientôt,\nElodie", ['prenom' => 'Dylan'], ['Date' => 'jeudi']);
$check('e-mail modifiable : mots remplacés et récapitulatif en gras',
    [$mail['text'], str_contains($mail['html'], '<strong>Date :</strong> jeudi')],
    ["Bonjour Dylan,\n\nDate : jeudi\n\nÀ bientôt,\nElodie\n", true]);

$texts = require dirname(__DIR__) . '/app/appointment-text.php';
$filled = array_fill_keys(['prenom', 'nom', 'prestation', 'date', 'tarif', 'telephone', 'telephone_elodie'], 'x');
foreach (array_keys(config('services')) as $id) {
    if (bookable_service($id)) {
        $text = appointment_text($id);
        $rendered = fill_placeholders($text['subject'], $filled) . mail_template($text['body'], $filled, ['Date' => 'x'])['text'];
        $check("appointment-text.php : texte de « $id » présent, sans {mot} inconnu", [isset($texts[$id]), str_contains($rendered, '{')], [true, false]);
    }
}
$request = array_fill_keys(['prenom', 'nom', 'prestation', 'telephone', 'telephone_elodie'], 'x');
$rendered = fill_placeholders($texts['demande']['subject'] ?? '', $request) . mail_template($texts['demande']['body'] ?? '', $request, ['Prestation(s)' => 'x'])['text'];
$check('appointment-text.php : accusé de réception « demande » présent, sans {mot} inconnu', [isset($texts['demande']), str_contains($rendered, '{')], [true, false]);
$check('prestation sans texte : confirmation simple', str_contains(appointment_text('reiki')['body'], '{details}'), true);

echo $failures ? "\n$failures test(s) en échec\n" : "\nTous les tests passent\n";
exit($failures ? 1 : 0);
