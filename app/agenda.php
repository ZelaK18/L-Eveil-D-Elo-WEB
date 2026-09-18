<?php

const DAYS_FR = ['', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
const MONTHS_FR = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

// Une prestation à formules se réserve par formule : c'est chacune d'elles qui a sa durée.
function bookable_service(string $id): ?array
{
    $service = config('services')[$id] ?? null;
    return !empty($service['available']) && (!empty($service['duration']) || !empty($service['offers'])) ? $service : null;
}

// Ce qui se réserve en ligne : chaque prestation disponible, ou chacune de ses formules si elle en a
// (clé « coaching.1 » pour la 2e formule du coaching). service : la prestation, pour son e-mail de confirmation.
function booking_options(): array
{
    $options = [];
    foreach (config('services') as $id => $service) {
        if (!bookable_service($id)) {
            continue;
        }
        $service['service'] = $id;
        if (empty($service['offers'])) {
            $options[$id] = $service;
        }
        foreach ($service['offers'] ?? [] as $index => $offer) {
            $options["$id.$index"] = array_replace($service, [
                'name'           => "{$service['name']} · {$offer['nom']}",
                'offer'          => $offer['nom'],
                'offer_duration' => $offer['duree'],
                'duration'       => (int) ($offer['duree_reservation'] ?? 0),
                'price_text'     => $offer['tarif'],
                'format'         => $offer['format'] ?? $service['format'],
            ]);
        }
    }
    return $options;
}

function duration_label(int $minutes): string
{
    return $minutes < 60 ? "$minutes min" : sprintf('%d h %02d', intdiv($minutes, 60), $minutes % 60);
}

// price_text : tarif d'une formule, écrit tel quel dans site-text.php (« Offert », « 330 CHF »…).
function price_label(array $service): string
{
    return $service['price_text'] ?? 'CHF ' . $service['price'] . (isset($service['price_unit']) ? ' / ' . $service['price_unit'] : '');
}

// Montant en francs pour Google : le prix d'une prestation, ou le premier nombre du tarif d'une formule
// (« 1'200 CHF » donne 1200). Aucun pour un tarif sans chiffre, comme « Offert ».
function price_amount(array $service): ?int
{
    if (!isset($service['price_text'])) {
        return (int) $service['price'];
    }
    $text = preg_replace("/(?<=\d)['’ \x{00A0}\x{202F}](?=\d{3}\b)/u", '', $service['price_text']);
    return preg_match('/\d+/', $text, $match) ? (int) $match[0] : null;
}

// « mercredi 16 septembre 2026 »
function day_fr(DateTimeInterface $date): string
{
    return DAYS_FR[(int) $date->format('N')] . ' ' . $date->format('j') . ' ' . MONTHS_FR[(int) $date->format('n')] . ' ' . $date->format('Y');
}

// « 9h00 »
function time_fr(DateTimeInterface $date): string
{
    return $date->format('G\hi');
}

// « mercredi 16 septembre 2026 à 9h00 »
function date_fr(DateTimeInterface $date): string
{
    return day_fr($date) . ' à ' . time_fr($date);
}

// « mercredi 16 septembre 2026, de 9h00 à 10h00 »
function period_fr(DateTimeInterface $start, DateTimeInterface $end): string
{
    return day_fr($start) . ', de ' . time_fr($start) . ' à ' . time_fr($end);
}

// Jours consécutifs aux mêmes heures : [['from' => 1, 'to' => 6, 'ranges' => [['09:00', '19:00']]], …].
function opening_groups(): array
{
    $groups = [];
    foreach (config('booking.hours') as $day => $ranges) {
        $last = array_key_last($groups);
        if ($ranges && $last !== null && $groups[$last]['to'] === $day - 1 && $groups[$last]['ranges'] === $ranges) {
            $groups[$last]['to'] = $day;
        } elseif ($ranges) {
            $groups[] = ['from' => $day, 'to' => $day, 'ranges' => $ranges];
        }
    }
    return $groups;
}

// Un jour de marge pour les journées entières et les pauses. $cache : secondes de réutilisation (0 pour réserver).
function events_around(DateTimeImmutable $from, DateTimeImmutable $to, int $cache = 0): array
{
    [$from, $to] = [$from->modify('-1 day'), $to->modify('+1 day')];
    if ($cache === 0) {
        return calendar_events($from, $to);
    }

    $key = $from->format('c') . '|' . $to->format('c');
    $stored = storage_read('calendar-cache');
    $entry = $stored['entries'][$key] ?? null;
    if ($entry && $entry['at'] > max(microtime(true) - $cache, $stored['cleared'] ?? 0)) {
        return $entry['events'];
    }

    $started = microtime(true);
    $events = calendar_events($from, $to);
    // Une réservation terminée pendant cette lecture la rend périmée : elle n'est pas gardée.
    with_lock('calendar-cache', function () use ($key, $started, $events, $cache): void {
        $stored = storage_read('calendar-cache');
        $cleared = $stored['cleared'] ?? 0;
        if ($started > $cleared) {
            $entries = array_filter($stored['entries'] ?? [], fn(array $entry) => $entry['at'] > microtime(true) - $cache);
            storage_write('calendar-cache', ['cleared' => $cleared, 'entries' => [$key => ['at' => $started, 'events' => $events]] + $entries]);
        }
    });
    return $events;
}

function forget_calendar_cache(): void
{
    with_lock('calendar-cache', fn() => storage_write('calendar-cache', ['cleared' => microtime(true), 'entries' => []]));
}

// Créneaux de chaque option réservable. Les options de même durée partagent un seul calcul.
function slots_by_option(array $events, DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $byDuration = [];
    $slots = [];
    foreach (booking_options() as $key => $option) {
        $slots[$key] = $byDuration[$option['duration']] ??= available_slots($option['duration'], $events, $from, $to, time(), config('booking'));
    }
    return $slots;
}

// Plage « Dispo » : événement sans titre ou dont le titre contient le mot. Un rendez-vous pris sur le site
// n'en est jamais une, même si le nom de la personne contient le mot.
function is_availability(array $event, array $rules): bool
{
    $keyword = trim($rules['availability_keyword']);
    return $keyword !== '' && empty($event['site'])
        && (trim($event['summary']) === '' || mb_stripos($event['summary'], $keyword) !== false);
}

// Heures de début tous les « interval » (ex. 15 min) dans chaque plage, là où la prestation tient sans empiéter
// sur un rendez-vous et ses pauses.
function available_slots(int $duration, array $events, DateTimeImmutable $from, DateTimeImmutable $to, int $now, array $rules): array
{
    $tz = new DateTimeZone($rules['timezone']);
    $length = $duration * 60;
    $buffer = $rules['buffer'] * 60;
    $step = $rules['interval'] * 60;
    $earliest = max($from->getTimestamp(), $now + $rules['min_notice'] * 3600);
    $latest = min($to->getTimestamp(), (new DateTimeImmutable('@' . $now))->setTimezone($tz)
        ->setTime(0, 0)->modify('+' . ($rules['max_days'] + 1) . ' days')->getTimestamp());
    $opens = fn(array $event) => is_availability($event, $rules);

    $windows = [];
    if (trim($rules['availability_keyword']) !== '') {
        foreach (array_filter($events, $opens) as $event) {
            $windows[] = [$event['start'], $event['end']];
        }
    } else {
        for ($day = $from->setTimezone($tz)->setTime(0, 0); $day < $to; $day = $day->modify('+1 day')) {
            foreach ($rules['hours'][(int) $day->format('N')] ?? [] as [$open, $close]) {
                $windows[] = [$day->modify($open)->getTimestamp(), $day->modify($close)->getTimestamp()];
            }
        }
    }

    $busy = [];
    foreach ($events as $event) {
        if (!$event['free'] && !$opens($event)) {
            $busy[] = [$event['start'] - $buffer, $event['end'] + $buffer];
        }
    }

    $slots = [];
    foreach ($windows as [$start, $end]) {
        $time = (int) ceil($start / $step) * $step;
        while ($time + $length <= $end) {
            $busyUntil = 0;
            foreach ($busy as [$busyStart, $busyEnd]) {
                if ($busyStart < $time + $length && $busyEnd > $time) {
                    $busyUntil = max($busyUntil, $busyEnd);
                }
            }
            if ($busyUntil) {
                $time = (int) ceil($busyUntil / $step) * $step;
                continue;
            }
            if ($time >= $earliest && $time + $length <= $latest) {
                $local = (new DateTimeImmutable('@' . $time))->setTimezone($tz);
                $slots[$local->format('Y-m-d')][$local->format('H:i')] = true;
            }
            $time += $step;
        }
    }

    ksort($slots);
    return array_map(function (array $times): array {
        $times = array_keys($times);
        sort($times);
        return $times;
    }, $slots);
}

// Zone occupée par un rendez-vous et ses pauses, et les plages « Dispo » qu'elle touche : [plages, début, fin].
function availability_hit(array $events, int $start, int $end, array $rules): array
{
    $busyStart = $start - $rules['buffer'] * 60;
    $busyEnd = $end + $rules['buffer'] * 60;
    $hit = array_filter($events, fn(array $event) => is_availability($event, $rules) && $event['start'] < $busyEnd && $event['end'] > $busyStart);
    return [$hit, $busyStart, $busyEnd];
}

// Plages « Dispo » occupées par un rendez-vous et ses pauses : raccourcies, coupées en deux ou supprimées,
// pour que l'agenda montre les mêmes disponibilités que le site.
// Renvoie [['update', id, champs], ['create', null, champs], ['delete', id, []]], heures en horodatages.
function availability_changes(array $events, int $start, int $end, array $rules): array
{
    [$hit, $busyStart, $busyEnd] = availability_hit($events, $start, $end, $rules);
    $changes = [];
    foreach ($hit as $event) {
        $before = $event['start'] < $busyStart;
        $after = $busyEnd < $event['end'];
        if ($before) {
            $changes[] = ['update', $event['id'], ['end' => $busyStart]];
        }
        if ($after && $before) {
            $changes[] = ['create', null, [
                'summary'      => $event['summary'],
                'start'        => $busyEnd,
                'end'          => $event['end'],
                'transparency' => $event['free'] ? 'transparent' : 'opaque',
            ]];
        } elseif ($after) {
            $changes[] = ['update', $event['id'], ['start' => $busyEnd]];
        }
        if (!$before && !$after) {
            $changes[] = ['delete', $event['id'], []];
        }
    }
    return $changes;
}

function apply_availability_changes(array $changes): void
{
    $tz = config('booking.timezone');
    foreach ($changes as [$action, $id, $fields]) {
        foreach (['start', 'end'] as $key) {
            if (isset($fields[$key])) {
                $fields[$key] = ['dateTime' => date(DATE_RFC3339, $fields[$key]), 'timeZone' => $tz];
            }
        }
        match ($action) {
            'update' => calendar_update_event($id, $fields),
            'create' => calendar_create_event($fields),
            'delete' => calendar_delete_event($id),
        };
    }
}

// Morceaux de plages « Dispo » qu'un rendez-vous retire, pauses comprises. Gardés dans le rendez-vous pour être
// rendus s'il est annulé. before / after : la plage continuait avant / après le morceau.
function carved_availability(array $events, int $start, int $end, array $rules): array
{
    [$hit, $busyStart, $busyEnd] = availability_hit($events, $start, $end, $rules);
    return array_values(array_map(fn(array $event) => [
        'start'  => max($event['start'], $busyStart),
        'end'    => min($event['end'], $busyEnd),
        'before' => $event['start'] < $busyStart,
        'after'  => $busyEnd < $event['end'],
        'free'   => $event['free'],
    ], $hit));
}

// Morceaux rendus à l'annulation : recollés aux plages voisines, ou recréés. $events : l'agenda sans le rendez-vous annulé.
// Là où la plage continuait, il doit rester une plage ou un autre rendez-vous du site (qui a pris la suite) :
// sinon Elodie a changé son agenda depuis, et le morceau n'est pas rendu. Un morceau déjà recouvert non plus.
// Même format que availability_changes().
function restored_availability(array $events, array $pieces, array $rules): array
{
    $dispo = array_values(array_filter($events, fn(array $event) => is_availability($event, $rules)));
    $buffer = $rules['buffer'] * 60;
    $booked = fn(int $time): bool => (bool) array_filter($events, fn(array $event) => $event['site']
        && $event['start'] - $buffer <= $time && $time <= $event['end'] + $buffer);
    $changes = [];
    foreach ($pieces as $piece) {
        $before = $after = null;
        foreach ($dispo as $index => $event) {
            if ($event['start'] < $piece['end'] && $event['end'] > $piece['start']) {
                continue 2;
            }
            if ($event['end'] === $piece['start']) {
                $before = $index;
            } elseif ($event['start'] === $piece['end']) {
                $after = $index;
            }
        }
        if (($piece['before'] && $before === null && !$booked($piece['start'])) || ($piece['after'] && $after === null && !$booked($piece['end']))) {
            continue;
        }

        if ($before !== null && $after !== null) {
            $dispo[$before]['end'] = $dispo[$after]['end'];
            $changes[] = ['update', $dispo[$before]['id'], ['end' => $dispo[$before]['end']]];
            $changes[] = ['delete', $dispo[$after]['id'], []];
            unset($dispo[$after]);
        } elseif ($before !== null) {
            $dispo[$before]['end'] = $piece['end'];
            $changes[] = ['update', $dispo[$before]['id'], ['end' => $piece['end']]];
        } elseif ($after !== null) {
            $dispo[$after]['start'] = $piece['start'];
            $changes[] = ['update', $dispo[$after]['id'], ['start' => $piece['start']]];
        } else {
            $changes[] = ['create', null, [
                'summary'      => $rules['availability_keyword'],
                'start'        => $piece['start'],
                'end'          => $piece['end'],
                'transparency' => $piece['free'] ? 'transparent' : 'opaque',
            ]];
        }
    }
    return $changes;
}

// Signature du lien d'annulation : 16 caractères, impossibles à deviner sans la clé app_key de secrets.php.
function cancel_signature(string $eventId): string
{
    return substr(base64url_encode(hash_hmac('sha256', "annulation|$eventId", app_key(), true)), 0, 16);
}

function cancel_url(string $eventId): string
{
    return config('site.url') . 'annuler.php?' . http_build_query(['r' => $eventId, 's' => cancel_signature($eventId)]);
}

// « 24 h »
function cancel_notice_label(): string
{
    return config('booking.cancel_notice') . ' h';
}

// Rendez-vous pris sur le site, tel que le lien d'annulation le montre. null pour un autre événement.
function site_appointment(array $event): ?array
{
    $data = $event['extendedProperties']['private'] ?? [];
    if (($data['source'] ?? '') !== 'site' || !isset($data['prestation'], $data['email'], $event['start']['dateTime'])) {
        return null;
    }
    $tz = new DateTimeZone(config('booking.timezone'));
    return [
        'prestation' => $data['prestation'],
        'prenom'     => $data['prenom'] ?? '',
        'nom'        => $data['nom'] ?? '',
        'email'      => $data['email'],
        'telephone'  => $data['telephone'] ?? '',
        'start'      => (new DateTimeImmutable($event['start']['dateTime']))->setTimezone($tz),
        'end'        => (new DateTimeImmutable($event['end']['dateTime'] ?? $event['start']['dateTime']))->setTimezone($tz),
        'dispo'      => json_decode($data['dispo'] ?? '[]', true) ?: [],
    ];
}
