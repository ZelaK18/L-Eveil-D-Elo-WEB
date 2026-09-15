<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('booking', 5, 3600);

$id = input('service');
$service = bookable_service($id);
$tz = new DateTimeZone(config('booking.timezone'));
$slot = input('date') . ' ' . input('time');
$start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $slot, $tz);
if (!$service || !$start || $start->format('Y-m-d H:i') !== $slot) {
    form_response(false, message('choisir_creneau'), 422);
}

$person = read_person();
$site = config('site');
$name = "{$person['prenom']} {$person['nom']}";
$message = $person['message'] !== '' ? $person['message'] : 'Aucun message';
$end = $start->modify("+{$service['duration']} minutes");

$event = [
    'summary'     => "{$service['name']} · $name",
    'description' => implode("\n", [
        "Réservé sur le site de {$site['name']}.",
        "Téléphone : {$person['telephone']}",
        "E-mail : {$person['email']}",
        '',
        'Message :',
        $message,
    ]),
    'start'       => ['dateTime' => $start->format(DATE_RFC3339), 'timeZone' => $tz->getName()],
    'end'         => ['dateTime' => $end->format(DATE_RFC3339), 'timeZone' => $tz->getName()],
    // Aucun invité : Google Agenda n'écrit jamais à la personne, seul le site lui envoie sa confirmation.
    // « source » repère les rendez-vous du site : ils restent occupés quel que soit le nom saisi.
    'extendedProperties' => ['private' => ['source' => 'site']],
] + ($service['visio'] ? [] : ['location' => "Par téléphone : {$person['telephone']}"]);

try {
    // Verrou : deux personnes ne peuvent pas prendre le même créneau au même instant.
    $created = with_lock('booking', function () use ($id, $start, $end, $event): ?array {
        // Jusqu'au surlendemain : un créneau qui déborde après minuit se vérifie comme dans le calendrier.
        $day = $start->setTime(0, 0);
        $until = $day->modify('+2 days');
        $events = events_around($day, $until);
        $free = slots_by_service($events, $day, $until)[$id][$start->format('Y-m-d')] ?? [];
        $created = null;
        if (in_array($start->format('H:i'), $free, true)) {
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
$period = day_fr($start) . ', de ' . time_fr($start) . ' à ' . time_fr($end);
$values = [
    'prenom'           => $person['prenom'],
    'nom'              => $person['nom'],
    'prestation'       => $service['name'],
    'date'             => $when,
    'tarif'            => price_label($service),
    'telephone'        => $person['telephone'],
    'telephone_elodie' => $site['phone_display'],
];

// Textes : textes/appointment-text.php (avis pour Elodie, confirmation propre à la prestation).
$emails = [
    [config('mail_to'), 'avis_reservation', $person['email'], [
        'Prestation' => "{$service['name']} ({$service['format']})",
        'Date'       => $period,
        'Nom'        => $person['nom'],
        'Prénom'     => $person['prenom'],
        'E-mail'     => $person['email'],
        'Téléphone'  => $person['telephone'],
        'Message'    => $message,
    ]],
    [$person['email'], $id, null, [
        'Prestation' => $service['name'],
        'Date'       => $period,
        'Tarif'      => price_label($service),
    ] + ($service['visio'] ? ['Format' => $service['format']] : ['Téléphone' => "je vous appelle au {$person['telephone']}"])],
];

// Le rendez-vous est déjà dans l'agenda : un e-mail qui échoue ne doit pas l'annuler.
foreach ($emails as [$to, $textKey, $replyTo, $details]) {
    try {
        $text = appointment_text($textKey);
        send_mail($to, fill_placeholders($text['subject'], $values), mail_template($text['body'], $values, $details), $replyTo);
    } catch (Throwable $e) {
        error_log("E-mail de réservation à $to : " . $e->getMessage());
    }
}

form_response(true, 'Rendez-vous confirmé.', 200, ['service' => $service['name'], 'when' => $when, 'visio' => $service['visio']]);
