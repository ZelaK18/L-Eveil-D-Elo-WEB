<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('booking', 5, 3600);

// Une prestation (« tirage ») ou l'une de ses formules (« coaching.1 »).
$id = input('service');
$service = booking_options()[$id] ?? null;
$tz = new DateTimeZone(config('booking.timezone'));
$slot = input('date') . ' ' . input('time');
$start = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/D', $slot)
    ? DateTimeImmutable::createFromFormat('!Y-m-d H:i', $slot, $tz) : false;
if (!$service || !$start || $start->format('Y-m-d H:i') !== $slot) {
    form_response(false, message('choisir_creneau'), 422);
}

$person = read_person();
try {
    $answers = validate_intake($service, $_POST);
} catch (InvalidArgumentException $e) {
    form_response(false, $e->getMessage(), 422);
}
$requestId = input('request_id');
if (!preg_match('/^[a-f0-9]{32}$/D', $requestId)) {
    form_response(false, message('page_expiree'), 422);
}
// Identifiant stable : renvoyer le même formulaire après une coupure ne crée pas un second rendez-vous.
$eventId = hash_hmac('sha256', 'booking-event|' . $requestId, app_key());
$recordName = 'booking-form-' . $eventId;
$fingerprint = hash('sha256', json_encode([$id, $slot, $person, $answers, booking_terms_version($service)], JSON_THROW_ON_ERROR));
// Le script de reprise des e-mails utilise le même verrou, propre à cette réservation.
hold_lock($recordName);
$site = config('site');
$name = "{$person['prenom']} {$person['nom']}";
$end = $start->modify("+{$service['duration']} minutes");

$event = [
    'id'          => $eventId,
    'summary'     => "{$service['name']} · $name",
    'description' => implode("\n", [
        "Réservé sur le site de {$site['name']}.",
        'Tarif : ' . price_label($service),
        "Téléphone : {$person['telephone']}",
        "E-mail : {$person['email']}",
        'Formulaire validé. Les réponses personnelles sont transmises séparément à Elodie par e-mail.',
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
    $created = with_lock('booking', function () use ($service, $person, $answers, $start, $end, $event, $recordName, $fingerprint, &$record): ?array {
        $record = storage_read($recordName);
        if ($record) {
            if (!hash_equals($record['fingerprint'], $fingerprint)) {
                throw new InvalidArgumentException('Ce formulaire a déjà été validé. Pour modifier votre rendez-vous, contactez Elodie ou utilisez le lien de confirmation.');
            }
            if (!empty($record['event'])) {
                return $record['event'];
            }
            // Reprise après une interruption entre l'envoi à Google et la réponse du serveur.
            $existing = calendar_get_event($event['id']);
            if ($existing) {
                $record['event'] = $existing;
                storage_write($recordName, $record);
                forget_calendar_cache();
                return $existing;
            }
        }
        // Jusqu'au surlendemain : un créneau qui déborde après minuit se vérifie comme dans le calendrier.
        $day = $start->setTime(0, 0);
        $until = $day->modify('+2 days');
        $events = events_around($day, $until);
        $free = available_slots($service['duration'], $events, $day, $until, time(), config('booking'))[$start->format('Y-m-d')] ?? [];
        $created = null;
        if (in_array($start->format('H:i'), $free, true)) {
            // Si la conservation échoue, aucun événement ni e-mail n'est créé.
            $record = $record ?: [
                'fingerprint' => $fingerprint,
                'receipt' => intake_receipt($service, $person, $answers, $start, $end),
            ];
            storage_write($recordName, $record);
            // Morceaux de plages « Dispo » retirés, rendus si le rendez-vous est annulé en ligne.
            // Google refuse une valeur de plus de 1024 caractères : au-delà, la plage se remettra à la main.
            $carved = json_encode(carved_availability($events, $start->getTimestamp(), $end->getTimestamp(), config('booking')));
            $event['extendedProperties']['private']['dispo'] = strlen($carved) <= 1024 ? $carved : '[]';
            $created = calendar_create_event($event);
            $record['event'] = $created;
            storage_write($recordName, $record);
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
} catch (InvalidArgumentException $e) {
    form_response(false, $e->getMessage(), 422);
} catch (GoogleNotConnected) {
    form_response(false, message('reservation_fermee'), 503);
} catch (GoogleError $e) {
    error_log('Réservation : ' . $e->getMessage());
    form_response(false, message('reservation_echouee'), 502);
} catch (RuntimeException $e) {
    error_log('Conservation réservation : ' . $e->getMessage());
    form_response(false, 'La validation n’a pas pu se terminer. Renvoyez ce même formulaire sans modifier vos réponses ; en cas de doute, contactez Elodie.', 503);
}

if (!$created) {
    form_response(false, message('creneau_pris'), 409, ['code' => 'slot_taken']);
}

if (!empty($record['response'])) {
    form_response(true, 'Rendez-vous confirmé.', 200, $record['response']);
}

record_attempt('booking');

$when = date_fr($start);
$values = person_values($person) + [
    'prestation'       => $service['name'],
    'date'             => $when,
    'tarif'            => price_label($service),
    'lien_annulation'  => cancel_url($created['id']),
    'delai_annulation' => cancel_notice_label(),
];

// Textes : textes/appointment-text.php (avis pour Elodie, confirmation propre à la prestation).
$emails = [
    [config('mail_to'), 'avis_reservation', $person['email'], intake_owner_mail_details($service, $record['receipt'])],
    [$person['email'], $service['service'], config('site.email'), intake_client_mail_details($service, $record['receipt'])],
];

// Chaque envoi est suivi séparément et peut être repris sans recréer le rendez-vous.
$record['emails'] ??= $emails;
$record['values'] ??= $values;
// PHP-FPM / LiteSpeed peuvent rendre la confirmation avant les appels de messagerie.
// En local ou sur un autre serveur, l'envoi reste synchrone.
$finishRequest = function_exists('fastcgi_finish_request') ? 'fastcgi_finish_request'
    : (function_exists('litespeed_finish_request') ? 'litespeed_finish_request' : null);
$deferred = $finishRequest !== null && str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$response = ['service' => $service['name'], 'when' => $when, 'visio' => $service['visio'],
    'email_sent' => !empty($record['sent'][1]), 'email_pending' => $deferred];
$record['response'] = $response;
try {
    // Rendez-vous et e-mails à envoyer doivent être conservés avant toute réponse anticipée.
    storage_write($recordName, $record);
    hold_lock('form-booking', release: true);
    if ($deferred) {
        ignore_user_abort(true);
        register_shutdown_function(static function () use ($finishRequest, $recordName, $record): void {
            $finishRequest();
            try {
                deliver_booking_emails($recordName, $record);
            } catch (Throwable $e) {
                error_log('Confirmation réservation différée : ' . $e->getMessage());
            }
        });
        form_response(true, 'Rendez-vous confirmé.', 200, $response);
    }
    deliver_booking_emails($recordName, $record);
} catch (Throwable $e) {
    error_log('Confirmation réservation : ' . $e->getMessage());
}

$response['email_sent'] = !empty($record['sent'][1]);
$response['email_pending'] = false;
$record['response'] = $response;
try {
    storage_write($recordName, $record);
} catch (RuntimeException $e) {
    error_log('État de la confirmation non conservé : ' . $e->getMessage());
}
form_response(true, 'Rendez-vous confirmé.', 200, $response);
