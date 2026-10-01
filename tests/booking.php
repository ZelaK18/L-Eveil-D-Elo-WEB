<?php
// php tests/booking.php — vrais contrôles et endpoint, agenda et e-mails simulés, stockage isolé.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
$failures = 0;
$check = function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    $failures += $ok ? 0 : 1;
};
$valid = function (string $id): array {
    $service = booking_options()[$id];
    $fields = [];
    foreach (intake_definition($service)['fields'] as $key => $field) {
        $fields[$key] = isset($field['options']) ? array_key_first($field['options']) : 'Réponse de test : <script>confidentiel</script>';
    }
    return [
        'service' => $id, 'date' => (new DateTimeImmutable('next monday'))->format('Y-m-d'), 'time' => '10:00',
        'nom' => 'Exemple', 'prenom' => 'Camille', 'email' => 'client@example.test', 'telephone' => '+41790000000',
        'naissance' => '1990-03-15', 'questionnaire' => $fields, 'request_id' => bin2hex(random_bytes(16)),
        'conditions_version' => booking_terms_version($service),
    ] + array_fill_keys(array_keys(intake_texts()['consents']), '1');
};
$reject = function (array $post, string $label) use ($check): void {
    try {
        validate_intake(booking_options()[$post['service']], $post);
        $check($label, false);
    } catch (InvalidArgumentException) {
        $check($label, true);
    }
};
foreach (booking_options() as $id => $service) {
    $post = $valid($id);
    $answers = validate_intake($service, $post);
    $check("questionnaire complet accepté : $id", count($answers) === count(intake_definition($service)['fields']) + 1);
    foreach (intake_definition($service)['fields'] as $field => $definition) {
        if ($definition['required']) {
            $invalid = $post;
            unset($invalid['questionnaire'][$field]);
            $reject($invalid, "$id : réponse obligatoire « $field » refusée si absente");
        }
    }
}
$post = $valid('tirage');
foreach (intake_texts()['consents'] as $key => $label) {
    $invalid = $post;
    unset($invalid[$key]);
    $reject($invalid, "accord $key obligatoire côté serveur");
}
foreach (['2015-01-01', '1990-02-31', '2040-01-01', ['1990-01-01'], "1990-01-01\0"] as $birth) {
    $reject(array_replace($post, ['naissance' => $birth]), 'date invalide ou minorité refusée');
}
$today = new DateTimeImmutable('2026-10-01');
$boundary = array_replace($post, ['naissance' => '2008-10-01']);
$check('18 ans le jour même acceptés', count(validate_intake(booking_options()['tirage'], $boundary, $today)) > 0);
$reject(array_replace($post, ['conditions_version' => 'ancienne-version']), 'conditions périmées refusées');
$invalid = $post;
$invalid['questionnaire']['domaine'] = 'inconnu';
$reject($invalid, 'option inventée refusée');
$invalid['questionnaire']['domaine'] = ['personnel'];
$reject($invalid, 'réponse sous forme de tableau refusée');
$invalid = $post;
$invalid['questionnaire']['question'] = str_repeat('é', 2001);
$reject($invalid, 'texte trop long refusé sans troncature');
$coaching = booking_options()['coaching.1'];
$check('les tarifs actuels du site alimentent le contrat', str_contains(booking_terms($coaching)['Prestation et tarif'], price_label($coaching)));
$changed = $coaching;
$changed['price_text'] = '999 CHF';
$check('modifier le tarif invalide la version présentée', booking_terms_version($changed) !== booking_terms_version($coaching));

// Une copie temporaire ne contient aucun secret ni client du site et n'appelle jamais Google/mail().
$temp = sys_get_temp_dir() . '/elo-booking-tests-' . bin2hex(random_bytes(6));
foreach (['', '/app', '/api', '/bin', '/textes', '/storage'] as $dir) mkdir($temp . $dir);
foreach (['app/helpers.php', 'app/mail.php', 'app/agenda.php', 'app/intake.php', 'app/config.php', 'api/booking.php', 'bin/retry-booking-mails.php', 'textes/booking-forms.php', 'textes/site-text.php', 'textes/mentions-legales.php', 'textes/appointment-text.php'] as $file) {
    copy(ROOT_DIR . '/' . $file, "$temp/$file");
}
file_put_contents("$temp/app/bootstrap.php", <<<'PHP'
<?php
define('ROOT_DIR', dirname(__DIR__));
require __DIR__ . '/helpers.php';
require __DIR__ . '/mail.php';
require __DIR__ . '/agenda.php';
require __DIR__ . '/intake.php';
date_default_timezone_set(config('booking.timezone'));
$case = json_decode(file_get_contents(ROOT_DIR . '/request.json'), true);
$_POST = $case['post'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REMOTE_ADDR'] = $case['ip'];
$time = (string) (time() - 10);
$_POST['jeton'] = $time . '.' . hash_hmac('sha256', $time, app_key());
register_shutdown_function(function () { file_put_contents(ROOT_DIR . '/status.json', json_encode(http_response_code())); });
class GoogleError extends RuntimeException {}
class GoogleNotConnected extends GoogleError {}
function calendar_events(DateTimeImmutable $from, DateTimeImmutable $until): array {
    $state = storage_read('test-state');
    $state['reads'] = ($state['reads'] ?? 0) + 1;
    storage_write('test-state', $state);
    if (($GLOBALS['case']['mode'] ?? '') === 'busy') return [];
    $day = new DateTimeImmutable($_POST['date']);
    return [['id' => 'availability', 'summary' => 'Dispo', 'start' => $day->setTime(9, 0)->getTimestamp(), 'end' => $day->setTime(18, 0)->getTimestamp(), 'free' => false, 'site' => false]];
}
function calendar_create_event(array $event): array {
    if (!isset($event['id'])) return $event; // Plages « Dispo » découpées par le code réel.
    $state = storage_read('test-state');
    $state['creates'] = ($state['creates'] ?? 0) + 1;
    $state['events'][$event['id']] = $event;
    storage_write('test-state', $state);
    if (($GLOBALS['case']['mode'] ?? '') === 'interrupted') throw new GoogleError('Réponse perdue après création', 502);
    return $event;
}
function calendar_get_event(string $id): ?array { return storage_read('test-state')['events'][$id] ?? null; }
function calendar_update_event(string $id, array $event): array { return $event; }
function calendar_delete_event(string $id): void {}
function google_can(string $scope): bool { return true; }
function google_token(): array { return ['email' => 'elodie@example.test']; }
function gmail_send(string $mime): void {
    if (($GLOBALS['case']['mode'] ?? '') === 'mail_failure') throw new RuntimeException('Échec simulé');
    $state = storage_read('test-state');
    $state['mails'][] = $mime;
    storage_write('test-state', $state);
}
PHP);
$read = function (string $name) use ($temp): array {
    $raw = @file_get_contents("$temp/storage/$name.php");
    return $raw === false ? [] : json_decode(substr($raw, strlen(STORAGE_GUARD)), true);
};
$run = function (array $post, string $mode = '') use ($temp): array {
    file_put_contents("$temp/request.json", json_encode(['post' => $post, 'mode' => $mode, 'ip' => $post['request_id']]));
    $process = proc_open([PHP_BINARY, '-d', 'extension_dir=' . ini_get('extension_dir'), "$temp/api/booking.php"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $out = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) throw new RuntimeException($out . $errors);
    try {
        return [json_decode($out, true, 512, JSON_THROW_ON_ERROR), json_decode(file_get_contents("$temp/status.json"))];
    } catch (JsonException $e) {
        throw new RuntimeException($out . $errors, previous: $e);
    }
};
$incomplete = $valid('pendule');
unset($incomplete['questionnaire']['question']);
[$result, $status] = $run($incomplete);
$check('API : formulaire incomplet refusé avant tout agenda/e-mail', $status === 422 && empty($read('test-state')));
$busy = $valid('tirage');
[$result, $status] = $run($busy, 'busy');
$check('API : créneau pris refusé, aucun e-mail ni dossier', $status === 409 && $result['code'] === 'slot_taken' && empty($read('test-state')['mails']) && !glob("$temp/storage/booking-form-*.php"));
$complete = $valid('coaching.2');
[$result, $status] = $run($complete);
$state = $read('test-state');
$check('API : validation crée un seul rendez-vous et deux e-mails', $status === 200 && $result['ok'] && $result['email_sent'] && $state['creates'] === 1 && count($state['mails']) === 2);
$event = array_values($state['events'])[0];
$check('API : réponses privées absentes de Google Agenda', !str_contains(json_encode($event), 'confidentiel') && !str_contains(json_encode($event), '1990'));
$files = glob("$temp/storage/booking-form-*.php");
$recordName = basename($files[0], '.php');
$record = $read($recordName);
$check('API : accords, réponses, identité, date et conditions conservés', isset($record['receipt']['answers'], $record['receipt']['validated_at'], $record['receipt']['conditions_hash'], $record['receipt']['privacy']) && count($record['receipt']['consents']) === 4);
$check('API : Elodie reçoit les réponses et peut répondre au client', $record['emails'][0][2] === $complete['email'] && str_contains(implode(' ', $record['emails'][0][3]), 'confidentiel'));
$rendered = mail_template(appointment_text('avis_reservation')['body'], $record['values'], $record['emails'][0][3]);
$check('API : les balises saisies sont neutralisées dans l’e-mail', str_contains($rendered['html'], '&lt;script&gt;') && !str_contains($rendered['html'], '<script>'));
$check('API : copie des conditions envoyée au client, sans réponses personnelles', isset($record['emails'][1][3]['Accords cochés'], $record['emails'][1][3]['Réservation et paiement']) && !str_contains(implode(' ', $record['emails'][1][3]), 'confidentiel'));
[$result, $status] = $run($complete);
$check('API : renvoyer le même formulaire ne double ni réservation ni e-mails', $status === 200 && $read('test-state')['creates'] === 1 && count($read('test-state')['mails']) === 2);
$edited = $complete;
$edited['time'] = '11:00';
[$result, $status] = $run($edited);
$check('API : réutiliser un accord pour un autre horaire est refusé', $status === 422 && $read('test-state')['creates'] === 1);
$mailFailure = $valid('tirage');
[$result, $status] = $run($mailFailure, 'mail_failure');
$check('API : une panne e-mail conserve le rendez-vous et est annoncée', $status === 200 && $result['ok'] && !$result['email_sent'] && $read('test-state')['creates'] === 2);
$run($complete); // Le transport redevient disponible, sans renvoyer les e-mails déjà réussis.
$retry = proc_open([PHP_BINARY, '-d', 'extension_dir=' . ini_get('extension_dir'), "$temp/bin/retry-booking-mails.php"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
stream_get_contents($pipes[1]);
$retryErrors = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$retryCode = proc_close($retry);
$check('reprise : seuls les deux e-mails en échec sont envoyés, sans nouveau rendez-vous', $retryCode === 0 && $retryErrors === '' && count($read('test-state')['mails']) === 4 && $read('test-state')['creates'] === 2);
$interrupted = $valid('pendule');
[$result, $status] = $run($interrupted, 'interrupted');
$check('API : coupure Google après création simulée', $status === 502 && $read('test-state')['creates'] === 3 && count($read('test-state')['mails']) === 4);
[$result, $status] = $run($interrupted);
$check('API : reprise après coupure retrouve le rendez-vous et envoie les confirmations une fois', $status === 200 && $result['email_sent'] && $read('test-state')['creates'] === 3 && count($read('test-state')['mails']) === 6);

// Suppression limitée au répertoire de test créé ci-dessus.
$root = realpath($temp);
if (!$root || !str_starts_with(str_replace('\\', '/', $root), str_replace('\\', '/', realpath(sys_get_temp_dir())) . '/elo-booking-tests-')) {
    throw new RuntimeException('Répertoire de test inattendu.');
}
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($iterator as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
rmdir($root);
echo $failures ? "\n$failures échec(s)\n" : "\nTous les tests de réservation passent.\n";
exit($failures ? 1 : 0);
