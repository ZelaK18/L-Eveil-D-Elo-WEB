<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('contact', 5, 3600);

$person = read_person();
$values = person_values($person);

try {
    send_text_mail(config('mail_to'), 'avis_demande', $values, contact_mail_details($person), $person['email']);
} catch (Throwable $e) {
    error_log('Formulaire de demande : ' . $e->getMessage());
    form_response(false, message('envoi_echoue'), 500);
}

record_attempt('contact');

$emailSent = true;
try {
    // Sans le message de la personne : le formulaire ne doit pas servir à envoyer un texte libre à n'importe quelle adresse.
    send_text_mail($person['email'], 'demande', $values, replyTo: config('site.email'));
} catch (Throwable $e) {
    $emailSent = false;
    error_log('Accusé de réception de la demande : ' . $e->getMessage());
}

form_response(true, message($emailSent ? 'demande_envoyee' : 'demande_sans_email'), extra: ['email_sent' => $emailSent]);
