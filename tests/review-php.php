<?php
// Exécuté uniquement contre la copie isolée préparée par review-browser.cjs.
require $argv[1] . '/site/app/bootstrap.php';
$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException($message);
}
function rejects(callable $call, string $message): void
{
    try { $call(); } catch (InvalidArgumentException) { check(true, $message); return; }
    check(false, $message);
}
$today = new DateTimeImmutable('2026-10-02');
foreach (booking_options() as $id => $service) {
    $post = ['naissance' => '1990-01-01', 'conditions_version' => booking_terms_version($service)];
    foreach (intake_texts()['consents'] as $key => $_) $post[$key] = '1';
    foreach (intake_definition($service)['fields'] as $key => $field) {
        $post['questionnaire'][$key] = ($field['type'] ?? '') === 'select'
            ? (string) array_key_first($field['options']) : 'Réponse fictive de test.';
    }
    check(count(validate_intake($service, $post, $today)) > 1, "$id : formulaire valide");
    foreach (['2015-01-01', '2008-10-03', '1990-02-30', '2030-01-01', '1899-01-01'] as $birth) {
        rejects(fn() => validate_intake($service, array_replace($post, ['naissance' => $birth]), $today), "$id : naissance refusée $birth");
    }
    check(count(validate_intake($service, array_replace($post, ['naissance' => '2008-10-02']), $today)) > 1, "$id : majorité le jour des 18 ans");
    rejects(fn() => validate_intake($service, array_replace($post, ['conditions_version' => 'ancienne-version']), $today), "$id : conditions périmées");
    foreach (array_keys(intake_texts()['consents']) as $key) {
        rejects(fn() => validate_intake($service, array_replace($post, [$key => '']), $today), "$id : accord manquant");
    }
    foreach (intake_definition($service)['fields'] as $key => $field) {
        $invalid = $post;
        $invalid['questionnaire'][$key] = ($field['type'] ?? '') === 'select' ? 'choix-inexistant' : str_repeat('é', ($field['max'] ?? 2000) + 1);
        rejects(fn() => validate_intake($service, $invalid, $today), "$id : réponse invalide $key");
    }
}
$tz = new DateTimeZone('Europe/Zurich');
$date = fn(string $value) => new DateTimeImmutable($value, $tz);
$event = fn(string $id, string $summary, string $start, string $end) => [
    'id' => $id, 'summary' => $summary, 'start' => $date($start)->getTimestamp(),
    'end' => $date($end)->getTimestamp(), 'free' => false, 'site' => false,
];
$rules = config('booking');
$events = [
    $event('dispo', 'Dispo', '2026-10-05 09:00', '2026-10-05 12:00'),
    $event('busy', 'Séance', '2026-10-05 10:00', '2026-10-05 10:30'),
];
$slots = available_slots(30, $events, $date('2026-10-05'), $date('2026-10-06'), $date('2026-10-01')->getTimestamp(), $rules);
check(($slots['2026-10-05'] ?? []) === ['09:00', '09:15', '10:45', '11:00', '11:15', '11:30'], 'Agenda : durée, pause et collision');
check(available_slots(30, [], $date('2026-10-05'), $date('2026-10-06'), $date('2026-10-01')->getTimestamp(), $rules) === [], 'Agenda : aucune réservation sans plage Dispo');
$winter = [$event('winter', 'Dispo', '2026-10-26 09:00', '2026-10-26 10:00')];
$slots = available_slots(30, $winter, $date('2026-10-26'), $date('2026-10-27'), $date('2026-10-01')->getTimestamp(), $rules);
check(($slots['2026-10-26'] ?? []) === ['09:00', '09:15', '09:30'], 'Agenda : heure suisse après changement d’heure');
$mail = mail_content(['Question' => '<script>test</script>']);
check(!str_contains($mail['html'], '<script>') && str_contains($mail['html'], '&lt;script&gt;'), 'E-mail : réponses échappées');
check(!str_contains(format_text('<img src=x onerror=alert(1)>'), '<img'), 'Textes : balises échappées');
echo "$checks contrôles PHP réussis (questionnaires, accords, agenda, échappement).\n";
