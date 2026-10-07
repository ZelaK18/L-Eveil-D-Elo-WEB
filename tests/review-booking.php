<?php
// php -d extension_dir=php/ext tests/review-booking.php
// Copies temporaires sans secrets ; Google et les e-mails sont entièrement simulés.
class GoogleError extends RuntimeException {}
class GoogleNotConnected extends GoogleError {}

function trace_call(string $name): void
{
    file_put_contents(ROOT_DIR . '/calls.txt', $name . "\n", FILE_APPEND | LOCK_EX);
}
function calendar_events(DateTimeImmutable $from, DateTimeImmutable $to): array
{
    trace_call('read');
    usleep(200000);
    if ($GLOBALS['mode'] === 'cache-invalidate') forget_calendar_cache();
    if ($GLOBALS['mode'] === 'taken') return [];
    $day = new DateTimeImmutable('+3 days midnight', new DateTimeZone('Europe/Zurich'));
    return [['id' => 'dispo', 'summary' => 'Dispo', 'start' => $day->setTime(9, 0)->getTimestamp(),
        'end' => $day->setTime(19, 0)->getTimestamp(), 'free' => false, 'site' => false]];
}
function calendar_create_event(array $event): array
{
    trace_call('create');
    storage_write('mock-event', $event);
    return $event;
}
function calendar_get_event(string $id): ?array
{
    if ($GLOBALS['mode'] === 'retry-unavailable') throw new GoogleError('Google temporairement indisponible');
    return storage_read('mock-event') ?: null;
}
function calendar_update_event(string $id, array $fields): array { trace_call('update'); return $fields; }
function calendar_delete_event(string $id): void { trace_call('delete'); }
function google_can(string $scope): bool { return true; }
function google_token(): array { return ['email' => 'sender@example.test']; }
function gmail_send(string $mime): void
{
    trace_call('mail');
    // RuntimeException évite aussi tout repli vers mail() dans le test d'échec.
    if (in_array($GLOBALS['mode'], ['failure', 'contact-failure'], true)) throw new RuntimeException('Échec simulé');
    usleep(200000);
}

$mode = $argv[1] ?? 'suite';
if ($mode !== 'suite') {
    $fixture = $argv[2];
    if (in_array($mode, ['deferred', 'failure', 'concurrent'], true)) {
        function fastcgi_finish_request(): bool
        {
            $records = glob(ROOT_DIR . '/storage/booking-form-*.php');
            $record = storage_read(basename($records[0], '.php'));
            if (empty($record['emails']) || empty($record['response']['email_pending'])) {
                throw new RuntimeException('Réponse avant conservation des e-mails');
            }
            // Dans le test concurrent, une autre requête peut déjà avoir pris ce verrou.
            if ($GLOBALS['mode'] !== 'concurrent') {
                $handle = fopen(storage_path('form-booking.lock'), 'c');
                if (!flock($handle, LOCK_EX | LOCK_NB)) throw new RuntimeException('Verrou global encore détenu');
                fclose($handle);
            }
            trace_call('finish');
            return true;
        }
    }
    if (in_array($mode, ['sync', 'deferred', 'failure', 'taken', 'concurrent'], true)) {
        $_POST = json_decode(file_get_contents($fixture . '/post.json'), true);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        require $fixture . '/api/booking.php';
        exit;
    }
    if (in_array($mode, ['contact', 'contact-failure'], true)) {
        $_POST = json_decode(file_get_contents($fixture . '/post.json'), true);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        require $fixture . '/api/contact.php';
        exit;
    }
    if (in_array($mode, ['retry', 'retry-unavailable'], true)) {
        require $fixture . '/bin/retry-booking-mails.php';
        exit;
    }
    require $fixture . '/app/bootstrap.php';
    if ($mode === 'prepare') {
        $service = booking_options()['tirage'];
        $time = (string) (time() - 5);
        $post = ['service' => 'tirage', 'date' => (new DateTimeImmutable('+3 days'))->format('Y-m-d'),
            'time' => '09:00', 'request_id' => str_repeat('a', 32), 'jeton' => $time . '.' . hash_hmac('sha256', $time, app_key()),
            'prenom' => 'Camille', 'nom' => 'Exemple', 'email' => 'camille@example.test', 'telephone' => '+41790000000',
            'consent' => '1', 'naissance' => '1990-01-01', 'conditions_version' => booking_terms_version($service)];
        foreach (intake_texts()['consents'] as $key => $_) $post[$key] = '1';
        foreach (intake_definition($service)['fields'] as $key => $field) {
            $post['questionnaire'][$key] = ($field['type'] ?? '') === 'select'
                ? (string) array_key_first($field['options']) : 'Réponse fictive.';
        }
        file_put_contents($fixture . '/post.json', json_encode($post));
    } elseif ($mode === 'invalidate') {
        forget_calendar_cache();
    } else {
        $from = new DateTimeImmutable('first day of this month midnight');
        events_around($from, $from->modify('+1 month'), $mode === 'fresh' ? 0 : 15);
    }
    exit;
}

$checks = 0;
function verify(bool $ok, string $label): void
{
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException($label);
}
function fixture(): string
{
    $root = dirname(__DIR__) . '/site';
    $dir = sys_get_temp_dir() . '/elo-booking-test-' . bin2hex(random_bytes(6));
    foreach (['app', 'textes', 'api', 'bin', 'storage'] as $part) mkdir($dir . '/' . $part, 0777, true);
    foreach (['app', 'textes', 'api', 'bin'] as $part) {
        foreach (glob($root . '/' . $part . '/*.php') as $file) {
            if (str_starts_with(basename($file), 'secrets') || basename($file) === 'google.php') continue;
            copy($file, $dir . '/' . $part . '/' . basename($file));
        }
    }
    file_put_contents($dir . '/app/google.php', '<?php // Google simulé par le lanceur.');
    file_put_contents($dir . '/app/secrets.php', "<?php return ['app_key' => 'test-booking-key-without-any-real-secret'];");
    return $dir;
}
function start_worker(string $mode, string $dir): array
{
    $process = proc_open([PHP_BINARY, '-d', 'extension_dir=' . ini_get('extension_dir'), __FILE__, $mode, $dir],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, options: ['bypass_shell' => true]);
    fclose($pipes[0]);
    return [$process, $pipes];
}
function finish_worker(array $worker): string
{
    [$process, $pipes] = $worker;
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    verify(proc_close($process) === 0, "Processus PHP : $out $err");
    return $out;
}
function run_worker(string $mode, string $dir): string { return finish_worker(start_worker($mode, $dir)); }
function calls(string $dir): array { return file_exists($dir . '/calls.txt') ? file($dir . '/calls.txt', FILE_IGNORE_NEW_LINES) : []; }
function record(string $dir): array
{
    $file = glob($dir . '/storage/booking-form-*.php')[0];
    $raw = file_get_contents($file);
    return json_decode(substr($raw, strpos($raw, '{')), true);
}

$cache = fixture();
$workers = [start_worker('cache', $cache), start_worker('cache', $cache), start_worker('cache', $cache)];
foreach ($workers as $worker) finish_worker($worker);
verify(calls($cache) === ['read'], 'Lectures concurrentes : un seul appel Google');
run_worker('fresh', $cache);
verify(calls($cache) === ['read', 'read'], 'Réservation : ignore toujours le cache');
run_worker('invalidate', $cache);
run_worker('cache', $cache);
verify(count(calls($cache)) === 3, 'Invalidation : nouvelle lecture');
run_worker('invalidate', $cache);
run_worker('cache-invalidate', $cache);
run_worker('cache', $cache);
verify(count(calls($cache)) === 5, 'Lecture invalidée pendant Google : jamais remise en cache');

foreach (['sync', 'deferred', 'failure', 'taken'] as $scenario) {
    $dir = fixture();
    run_worker('prepare', $dir);
    $response = json_decode(run_worker($scenario, $dir), true, flags: JSON_THROW_ON_ERROR);
    $log = calls($dir);
    if ($scenario === 'taken') {
        verify(!$response['ok'] && $response['code'] === 'slot_taken', 'Créneau pris : refus');
        verify($log === ['read'], 'Créneau pris : aucun rendez-vous ni e-mail');
        continue;
    }
    verify($response['ok'], "$scenario : réservation confirmée");
    verify($response['visio'] === true, "$scenario : tirage confirmé en visio");
    verify(str_contains(record($dir)['event']['description'], 'En visio sur Google Meet'), "$scenario : format visio précisé dans l’agenda");
    verify(!isset(record($dir)['event']['location']), "$scenario : aucun lieu par message pour le tirage en visio");
    verify(!str_contains(json_encode(record($dir)['emails'], JSON_UNESCAPED_UNICODE), 'Par message'), "$scenario : récapitulatif du tirage sans ancien format par message");
    verify($response['email_pending'] === ($scenario !== 'sync'), "$scenario : statut e-mail exact");
    verify(count(array_filter($log, fn($call) => $call === 'create')) === 1, "$scenario : un seul rendez-vous");
    if ($scenario !== 'sync') verify(array_search('finish', $log, true) < array_search('mail', $log, true), 'Réponse envoyée avant les e-mails');
    verify(record($dir)['response']['email_pending'] === false, "$scenario : fin de traitement conservée");
    verify(record($dir)['response']['email_sent'] === ($scenario !== 'failure'), "$scenario : succès/échec conservé");
    if ($scenario === 'failure') {
        run_worker('retry', $dir);
        verify(record($dir)['response']['email_sent'], 'Reprise : e-mails envoyés');
    }
    $before = calls($dir);
    $again = json_decode(run_worker('sync', $dir), true, flags: JSON_THROW_ON_ERROR);
    verify($again['ok'] && $again['email_sent'] && !$again['email_pending'], 'Même formulaire : réponse conservée');
    run_worker('retry', $dir);
    verify(calls($dir) === $before, 'Même formulaire et reprise : aucun doublon Google/e-mail');
}
$concurrent = fixture();
run_worker('prepare', $concurrent);
$workers = [start_worker('concurrent', $concurrent), start_worker('concurrent', $concurrent)];
foreach ($workers as $worker) {
    $response = json_decode(finish_worker($worker), true, flags: JSON_THROW_ON_ERROR);
    verify($response['ok'], 'Double envoi concurrent : confirmation');
}
$counts = array_count_values(calls($concurrent));
verify(($counts['create'] ?? 0) === 1 && ($counts['mail'] ?? 0) === 2, 'Double envoi concurrent : un rendez-vous, deux e-mails au total');
echo "$checks contrôles réussis : cache concurrent, invalidation, créneau pris, confirmation différée, repli synchrone, reprise sans doublon.\n";

// Régressions de l'audit : suppression, déplacement et panne ne sont pas équivalents.
foreach (['cancelled', 'changed', 'past', 'unavailable'] as $state) {
    $dir = fixture();
    run_worker('prepare', $dir);
    run_worker('failure', $dir);
    $before = calls($dir);
    $event = record($dir)['event'];
    if ($state === 'cancelled') $event = [];
    if ($state === 'changed') $event['start']['dateTime'] = (new DateTimeImmutable($event['start']['dateTime']))->modify('+1 hour')->format(DATE_RFC3339);
    if ($state === 'past') {
        // Le dossier et Google sont d'accord sur le créneau, mais il est passé.
        $event['start']['dateTime'] = (new DateTimeImmutable('-1 hour'))->format(DATE_RFC3339);
        $event['end']['dateTime'] = (new DateTimeImmutable('-40 minutes'))->format(DATE_RFC3339);
        $saved = record($dir);
        $saved['event'] = $event;
        file_put_contents(glob($dir . '/storage/booking-form-*.php')[0], "<?php exit; ?>\n" . json_encode($saved));
    }
    file_put_contents($dir . '/storage/mock-event.php', "<?php exit; ?>\n" . json_encode($event));
    run_worker($state === 'unavailable' ? 'retry-unavailable' : 'retry', $dir);
    verify(calls($dir) === $before, "$state : aucune confirmation périmée envoyée");
    if ($state === 'unavailable') {
        verify(!isset(record($dir)['status']), 'Panne Google : ne marque pas le rendez-vous annulé');
        run_worker('retry', $dir);
        verify(record($dir)['response']['email_sent'], 'Google rétabli : reprise possible');
    } else {
        verify(record($dir)['status'] === $state, "$state : état conservé");
        $response = json_decode(run_worker('sync', $dir), true, flags: JSON_THROW_ON_ERROR);
        verify(!$response['ok'], "$state : renvoyer le formulaire ne confirme pas l’ancien rendez-vous");
        verify(calls($dir) === $before, "$state : ne recrée pas le rendez-vous");
    }
}

// Réessai d'une demande après perte de réponse : ni double e-mail, ni message stocké.
$contact = fixture();
run_worker('prepare', $contact);
$response = json_decode(run_worker('contact', $contact), true, flags: JSON_THROW_ON_ERROR);
verify($response['ok'], 'Contact : demande envoyée');
$before = calls($contact);
$workers = [start_worker('contact', $contact), start_worker('contact', $contact)];
foreach ($workers as $worker) verify(json_decode(finish_worker($worker), true)['ok'], 'Contact : réessai accepté');
verify(calls($contact) === $before, 'Contact : aucun doublon après deux réessais simultanés');
$receipt = file_get_contents($contact . '/storage/contact-receipts.php');
verify(!str_contains($receipt, 'Camille') && !str_contains($receipt, 'example.test'), 'Contact : pas de coordonnées en clair dans la trace');
$post = json_decode(file_get_contents($contact . '/post.json'), true);
$post['prenom'] = 'Autre';
file_put_contents($contact . '/post.json', json_encode($post));
verify(!json_decode(run_worker('contact', $contact), true)['ok'], 'Contact : même identifiant avec contenu modifié refusé');
verify(calls($contact) === $before, 'Contact modifié : pas de nouvel e-mail');
$contact = fixture();
run_worker('prepare', $contact);
verify(!json_decode(run_worker('contact-failure', $contact), true)['ok'], 'Contact : erreur annoncée');
verify(json_decode(run_worker('contact', $contact), true)['ok'], 'Contact : reprise après échec d’envoi');
echo "$checks contrôles au total, avec confirmations périmées et réessais du contact.\n";
