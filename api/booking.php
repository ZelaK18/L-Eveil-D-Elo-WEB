<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('booking', 5, 3600);

$id = input('service');
$service = bookable_service($id);
$tz = new DateTimeZone(config('booking.timezone'));
$slot = input('date') . ' ' . input('time');
$start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $slot, $tz);
if (!$service || !$start || $start->format('Y-m-d H:i') !== $slot) {
    form_response(false, 'Choisissez une prestation, un jour et une heure.', 422);
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
    form_response(false, BOOKING_CLOSED, 503);
} catch (GoogleError $e) {
    error_log('Réservation : ' . $e->getMessage());
    form_response(false, "La réservation n'a pas pu être enregistrée. Réessayez dans un instant ou utilisez le formulaire de demande.", 502);
}

if (!$created) {
    form_response(false, "Ce créneau vient d'être pris. Choisissez-en un autre.", 409, ['code' => 'slot_taken']);
}

record_attempt('booking');

$when = date_fr($start);
$period = day_fr($start) . ', de ' . time_fr($start) . ' à ' . time_fr($end);

$confirmation = appointment_text($id);
$values = [
    'prenom'           => $person['prenom'],
    'nom'              => $person['nom'],
    'prestation'       => $service['name'],
    'date'             => $when,
    'tarif'            => price_label($service),
    'telephone'        => $person['telephone'],
    'telephone_elodie' => $site['phone_display'],
];

$emails = [
    [
        config('mail_to'),
        "Nouveau rendez-vous : {$service['name']}, $when",
        mail_content(
            "$name a réservé un rendez-vous depuis le site.",
            [
                'Prestation'    => "{$service['name']} ({$service['format']})",
                'Date'          => $period,
                'Nom'           => $person['nom'],
                'Prénom'        => $person['prenom'],
                'E-mail'        => $person['email'],
                'Téléphone'     => $person['telephone'],
            ],
            ['Message' => $message],
        ),
        $person['email'],
    ],
    [
        $person['email'],
        fill_placeholders($confirmation['subject'], $values),
        mail_template($confirmation['body'], $values, [
            'Prestation' => $service['name'],
            'Date'       => $period,
            'Tarif'      => price_label($service),
        ] + ($service['visio'] ? ['Format' => $service['format']] : ['Téléphone' => "je vous appelle au {$person['telephone']}"])),
        null,
    ],
];

// Le rendez-vous est déjà dans l'agenda : un e-mail qui échoue ne doit pas l'annuler.
foreach ($emails as [$to, $subject, $content, $replyTo]) {
    try {
        send_mail($to, $subject, $content, $replyTo);
    } catch (Throwable $e) {
        error_log("E-mail de réservation à $to : " . $e->getMessage());
    }
}

form_response(true, 'Rendez-vous confirmé.', 200, ['service' => $service['name'], 'when' => $when, 'visio' => $service['visio']]);
