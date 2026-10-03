<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('contact', 5, 3600);

$person = read_person();
$values = person_values($person);
$requestId = input('request_id');
if (!preg_match('/^[a-f0-9]{32}$/D', $requestId)) {
    form_response(false, message('page_expiree'), 422);
}
// Pas de contenu du formulaire enregistré : seulement une empreinte et l'état d'envoi.
// Le verrou form-contact pris par guard_form protège aussi les réessais simultanés.
$now = time();
$receipts = array_filter(storage_read('contact-receipts'), fn(array $receipt) => ($receipt['at'] ?? 0) > $now - 2 * 86400);
$fingerprint = hash_hmac('sha256', json_encode($person, JSON_THROW_ON_ERROR), app_key());
$receipt = $receipts[$requestId] ?? ['at' => $now, 'fingerprint' => $fingerprint];
if (!hash_equals($receipt['fingerprint'], $fingerprint)) {
    form_response(false, message('demande_modifiee'), 409);
}
ignore_user_abort(true);
$saveReceipt = function () use (&$receipts, &$receipt, $requestId): void {
    $receipts[$requestId] = $receipt;
    storage_write('contact-receipts', $receipts);
};

try {
    $saveReceipt();
    if (empty($receipt['owner_sent'])) {
        send_text_mail(config('mail_to'), 'avis_demande', $values, contact_mail_details($person), $person['email']);
        $receipt['owner_sent'] = true;
        $saveReceipt();
        record_attempt('contact');
    }
} catch (Throwable $e) {
    error_log('Formulaire de demande : ' . $e->getMessage());
    form_response(false, message('demande_incertaine'), 503);
}

$emailSent = !empty($receipt['client_sent']);
try {
    // Sans le message de la personne : le formulaire ne doit pas servir à envoyer un texte libre à n'importe quelle adresse.
    if (!$emailSent) {
        send_text_mail($person['email'], 'demande', $values, replyTo: config('site.email'));
        $receipt['client_sent'] = $emailSent = true;
        $saveReceipt();
    }
} catch (Throwable $e) {
    $emailSent = false;
    error_log('Accusé de réception de la demande : ' . $e->getMessage());
}

form_response(true, message($emailSent ? 'demande_envoyee' : 'demande_sans_email'), extra: ['email_sent' => $emailSent]);
