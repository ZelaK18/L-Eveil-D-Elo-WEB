<?php
// Reprendre les confirmations dont l'envoi a échoué : php bin/retry-booking-mails.php
// Ne recrée aucun rendez-vous et n'envoie que les e-mails marqués comme non envoyés.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
foreach (glob(storage_path('booking-form-*.php')) ?: [] as $file) {
    $name = basename($file, '.php');
    if (!preg_match('/^booking-form-[a-f0-9]{64}$/D', $name)) {
        continue;
    }
    with_lock($name, function () use ($name): void {
        $record = storage_read($name);
        if (empty($record['event']) || empty($record['emails'])) {
            return;
        }
        try {
            deliver_booking_emails($name, $record);
            $status = $record['status'] ?? 'active';
            echo $name . ' : ' . ($status !== 'active' ? 'ignoré (' . $status . ')'
                : (count(array_filter($record['sent'] ?? [])) === 2 ? 'OK' : 'à reprendre')) . PHP_EOL;
        } catch (Throwable $e) {
            // L'indisponibilité d'un rendez-vous ne doit pas empêcher les autres reprises.
            error_log('Reprise ' . $name . ' : ' . $e->getMessage());
            echo $name . ' : vérification/envoi à reprendre' . PHP_EOL;
        }
    });
}
