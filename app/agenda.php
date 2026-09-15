<?php

const DAYS_FR = ['', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
const MONTHS_FR = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const BOOKING_CLOSED = "La réservation en ligne n'est pas encore ouverte. En attendant, le formulaire de demande fonctionne.";
const REQUEST_SENT = 'Merci ! Votre demande est bien partie, une confirmation vient de vous être envoyée par e-mail. Je vous réponds sous 48 h.';

function bookable_service(string $id): ?array
{
    $service = config('services')[$id] ?? null;
    return !empty($service['available']) && !empty($service['duration']) ? $service : null;
}

function appointment_text(string $id): array
{
    static $texts;
    $texts ??= require __DIR__ . '/appointment-text.php';
    return $texts[$id] ?? [
        'subject' => 'Rendez-vous confirmé : {prestation}, {date}',
        'body'    => "Bonjour {prenom},\n\nVotre rendez-vous est confirmé.\n\n{details}\n\nÀ bientôt,\nElodie",
    ];
}

function request_choices(): array
{
    $choices = [];
    foreach (config('services') as $service) {
        if ($service['available']) {
            $choices[$service['name']] = $service['format'];
        }
    }
    return $choices + ['Bon cadeau' => 'Format papier ou PDF'];
}

function duration_label(int $minutes): string
{
    return $minutes < 60 ? "$minutes min" : sprintf('%d h %02d', intdiv($minutes, 60), $minutes % 60);
}

function price_label(array $service): string
{
    return 'CHF ' . $service['price'] . (isset($service['price_unit']) ? ' / ' . $service['price_unit'] : '');
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

// « Du lundi au samedi, 9h à 19h »
function opening_label(): string
{
    $hour = fn(string $time) => (int) $time . 'h' . (str_ends_with($time, ':00') ? '' : substr($time, 3));
    return implode(' · ', array_map(fn(array $group) =>
        ($group['from'] === $group['to'] ? ucfirst(DAYS_FR[$group['from']]) : 'Du ' . DAYS_FR[$group['from']] . ' au ' . DAYS_FR[$group['to']])
        . ', ' . implode(' et ', array_map(fn(array $range) => $hour($range[0]) . ' à ' . $hour($range[1]), $group['ranges'])),
        opening_groups()));
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

function slots_by_service(array $events, DateTimeImmutable $from, DateTimeImmutable $to): array
{
    $slots = [];
    foreach (array_keys(config('services')) as $id) {
        if ($service = bookable_service($id)) {
            $slots[$id] = available_slots($service['duration'], $events, $from, $to, time(), config('booking'));
        }
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

// Plages « Dispo » occupées par un rendez-vous et ses pauses : raccourcies, coupées en deux ou supprimées,
// pour que l'agenda montre les mêmes disponibilités que le site.
// Renvoie [['update', id, champs], ['create', null, champs], ['delete', id, []]], heures en horodatages.
function availability_changes(array $events, int $start, int $end, array $rules): array
{
    $busyStart = $start - $rules['buffer'] * 60;
    $busyEnd = $end + $rules['buffer'] * 60;
    $changes = [];
    foreach ($events as $event) {
        if (!is_availability($event, $rules) || $event['end'] <= $busyStart || $event['start'] >= $busyEnd) {
            continue;
        }
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
