<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$tz = new DateTimeZone(config('booking.timezone'));
$first = (new DateTimeImmutable('first day of this month', $tz))->setTime(0, 0);
$last = (new DateTimeImmutable('today', $tz))->modify('+' . config('booking.max_days') . ' days')->modify('first day of this month');
$month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($_GET['month'] ?? ''))
    ? new DateTimeImmutable($_GET['month'] . '-01', $tz)
    : $first;
$month = min(max($month, $first), $last);

try {
    // Lecture de l'agenda gardée 15 secondes : les visites rapprochées n'appellent Google qu'une fois.
    $next = $month->modify('+1 month');
    $slots = slots_by_service(events_around($month, $next, 15), $month, $next);
} catch (GoogleNotConnected) {
    json_response(['ok' => false, 'message' => BOOKING_CLOSED], 503);
} catch (GoogleError $e) {
    error_log('Disponibilités : ' . $e->getMessage());
    json_response(['ok' => false, 'message' => "L'agenda ne répond pas pour le moment. Réessayez plus tard ou utilisez le formulaire de demande."], 502);
}

json_response([
    'ok'       => true,
    'month'    => $month->format('Y-m'),
    'min'      => $first->format('Y-m'),
    'max'      => $last->format('Y-m'),
    'services' => array_map(fn(array $days) => (object) $days, $slots),
]);
