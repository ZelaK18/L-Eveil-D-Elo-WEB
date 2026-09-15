<?php
require dirname(__DIR__) . '/app/bootstrap.php';

guard_form('contact', 5, 3600);

$person = read_person();
$choices = request_choices();
$chosen = array_values(array_intersect(array_keys($choices), input_list('prestation')));
$prestations = $chosen ? implode(', ', $chosen) : 'Non précisé';
$formats = array_unique(array_map(fn(string $choice) => $choices[$choice], $chosen));
$format = $formats ? implode(' et ', $formats) : 'Non précisé';
$message = $person['message'] !== '' ? $person['message'] : 'Aucun message';

$content = mail_content([
    'Nom'           => $person['nom'],
    'Prénom'        => $person['prenom'],
    'E-mail'        => $person['email'],
    'Téléphone'     => $person['telephone'],
    'Prestation(s)' => $prestations,
    'Format'        => $format,
], ['Message' => $message]);

try {
    send_mail(config('mail_to'), "Formulaire de contact de {$person['nom']} {$person['prenom']}", $content, $person['email']);
} catch (Throwable $e) {
    error_log('Formulaire de demande : ' . $e->getMessage());
    form_response(false, "L'envoi a échoué. Vous pouvez m'écrire directement par e-mail ou par téléphone.", 500);
}

record_attempt('contact');

$text = appointment_text('demande');
$values = [
    'prenom'           => $person['prenom'],
    'nom'              => $person['nom'],
    'prestation'       => $prestations,
    'telephone'        => $person['telephone'],
    'telephone_elodie' => config('site.phone_display'),
];
try {
    // Sans le message de la personne : le formulaire ne doit pas servir à envoyer un texte libre à n'importe quelle adresse.
    send_mail($person['email'], fill_placeholders($text['subject'], $values), mail_template($text['body'], $values, [
        'Prestation(s)' => $prestations,
        'Format'        => $format,
    ]));
} catch (Throwable $e) {
    error_log('Accusé de réception de la demande : ' . $e->getMessage());
}

form_response(true, REQUEST_SENT);
