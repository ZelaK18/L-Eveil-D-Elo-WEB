<?php
// Reprendre les confirmations dont l'envoi a échoué : php bin/retry-booking-mails.php
// Ne recrée aucun rendez-vous et n'envoie que les e-mails marqués comme non envoyés.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
with_lock('form-booking', function (): void {
    foreach (glob(storage_path('booking-form-*.php')) ?: [] as $file) {
        $name = basename($file, '.php');
        if (!preg_match('/^booking-form-[a-f0-9]{64}$/D', $name)) {
            continue;
        }
        $record = storage_read($name);
        if (empty($record['event']) || empty($record['emails'])) {
            continue;
        }
        deliver_booking_emails($name, $record);
        if (isset($record['response'])) {
            $record['response']['email_sent'] = !empty($record['sent'][1]);
            storage_write($name, $record);
        }
        echo $name . ' : ' . (count(array_filter($record['sent'] ?? [])) === 2 ? 'OK' : 'à reprendre') . PHP_EOL;
    }
});
