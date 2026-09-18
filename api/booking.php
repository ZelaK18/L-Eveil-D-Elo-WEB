<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('booking', 5, 3600);

// Une prestation (« tirage ») ou l'une de ses formules (« coaching.1 »).
$id = input('service');
$service = booking_options()[$id] ?? null;
$tz = new DateTimeZone(config('booking.timezone'));
$slot = input('date') . ' ' . input('time');
$start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $slot, $tz);
if (!$service || !$start || $start->format('Y-m-d H:i') !== $slot) {
    form_response(false, message('choisir_creneau'), 422);
}

$person = read_person();
$site = config('site');
$name = "{$person['prenom']} {$person['nom']}";
$contact = person_details($person);
$end = $start->modify("+{$service['duration']} minutes");

$event = [
    'summary'     => "{$service['name']} · $name",
    'description' => implode("\n", [
        "Réservé sur le site de {$site['name']}.",
        'Tarif : ' . price_label($service),
        "Téléphone : {$person['telephone']}",
        "E-mail : {$person['email']}",
        '',
        'Message :',
        $contact['Message'],
    ]),
    'start'       => ['dateTime' => $start->format(DATE_RFC3339), 'timeZone' => $tz->getName()],
    'end'         => ['dateTime' => $end->format(DATE_RFC3339), 'timeZone' => $tz->getName()],
    // Aucun invité : Google Agenda n'écrit jamais à la personne, seul le site lui envoie sa confirmation.
    // « source » repère les rendez-vous du site : ils restent occupés quel que soit le nom saisi.
    // Le reste sert au lien d'annulation : ce que la page montre et qui prévenir.
    'extendedProperties' => ['private' => [
        'source'     => 'site',
        'prestation' => $service['name'],
        'prenom'     => $person['prenom'],
        'nom'        => $person['nom'],
        'email'      => $person['email'],
        'telephone'  => $person['telephone'],
    ]],
] + ($service['visio'] ? [] : ['location' => "Par message : {$person['telephone']}"]);

try {
    // Verrou : deux personnes ne peuvent pas prendre le même créneau au même instant.
    $created = with_lock('booking', function () use ($id, $start, $end, $event): ?array {
        // Jusqu'au surlendemain : un créneau qui déborde après minuit se vérifie comme dans le calendrier.
        $day = $start->setTime(0, 0);
        $until = $day->modify('+2 days');
        $events = events_around($day, $until);
        $free = slots_by_option($events, $day, $until)[$id][$start->format('Y-m-d')] ?? [];
        $created = null;
        if (in_array($start->format('H:i'), $free, true)) {
            // Morceaux de plages « Dispo » retirés, rendus si le rendez-vous est annulé en ligne.
            // Google refuse une valeur de plus de 1024 caractères : au-delà, la plage se remettra à la main.
            $carved = json_encode(carved_availability($events, $start->getTimestamp(), $end->getTimestamp(), config('booking')));
            $event['extendedProperties']['private']['dispo'] = strlen($carved) <= 1024 ? $carved : '[]';
            $created = calendar_create_event($event);
            try {
                apply_availability_changes(availability_changes($events, $start->getTimestamp(), $end->getTimestamp(), config('booking')));
            } catch (GoogleError $e) {
                // Le rendez-vous est enregistré, et une plage « Dispo » non raccourcie reste bloquée par lui.
                error_log('Plage Dispo : ' . $e->getMessage());
            }
        }
        // Réservé ou déjà pris, l'agenda a changé : le cache des disponibilités ne doit plus servir.
        forget_calendar_cache();
        return $created;
    });
} catch (GoogleNotConnected) {
    form_response(false, message('reservation_fermee'), 503);
} catch (GoogleError $e) {
    error_log('Réservation : ' . $e->getMessage());
    form_response(false, message('reservation_echouee'), 502);
}

if (!$created) {
    form_response(false, message('creneau_pris'), 409, ['code' => 'slot_taken']);
}

record_attempt('booking');

$when = date_fr($start);
$period = period_fr($start, $end);
$values = person_values($person) + [
    'prestation'       => $service['name'],
    'date'             => $when,
    'tarif'            => price_label($service),
    'lien_annulation'  => cancel_url($created['id']),
    'delai_annulation' => cancel_notice_label(),
];

// Textes : textes/appointment-text.php (avis pour Elodie, confirmation propre à la prestation).
$emails = [
    [config('mail_to'), 'avis_reservation', $person['email'], [
        'Prestation' => "{$service['name']} ({$service['format']})",
        'Date'       => $period,
        'Tarif'      => price_label($service),
    ] + $contact],
    [$person['email'], $service['service'], null, [
        'Prestation' => $service['name'],
        'Date'       => $period,
        'Tarif'      => price_label($service),
        // « Par message, au 079… » : la personne voit sur quel numéro Elodie la contactera.
        'Format'     => $service['visio'] ? $service['format'] : "{$service['format']}, au {$person['telephone']}",
    ]],
];

// Le rendez-vous est déjà dans l'agenda : un e-mail qui échoue ne doit pas l'annuler.
send_text_mails($emails, $values, 'Réservation');

form_response(true, 'Rendez-vous confirmé.', 200, ['service' => $service['name'], 'when' => $when, 'visio' => $service['visio']]);
