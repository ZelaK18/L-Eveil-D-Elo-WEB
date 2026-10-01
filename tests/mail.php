<?php
// php tests/mail.php : rendu uniquement, aucun envoi ni appel Google.
// Ajouter --preview pour créer les aperçus fictifs HTML/texte dans storage/mail-preview/.
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
$previews = [];
$preview = function (string $name, string $title, array $content) use ($argv, &$previews): void {
    if (!in_array('--preview', $argv, true)) return;
    $directory = ROOT_DIR . '/storage/mail-preview';
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    file_put_contents("$directory/$name.html", '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . '</title><body style="margin:16px">' . $content['html'] . '</body></html>');
    file_put_contents("$directory/$name.txt", $content['text']);
    $previews[$name] = $title;
};

$legacy = mail_template("Bonjour {prenom},\n\n{details}", ['prenom' => 'Camille'], ['Date' => 'vendredi', 'Message' => "Ligne 1\nLigne 2"]);
$check('ancien récapitulatif associatif toujours lisible', $legacy['text'] === "Bonjour Camille,\n\nDate : vendredi\nMessage :\nLigne 1\nLigne 2\n");
$field = mail_content(['Question' => mail_below('Une seule ligne <test> & suite'), 'Réponse' => mail_below("Première ligne\r\nDeuxième ligne")]);
$check('retour imposé pour une réponse sur une seule ligne', str_contains($field['text'], "Question :\nUne seule ligne <test> & suite\nRéponse :\n"));
$check('HTML échappé et retours Windows préservés', str_contains($field['html'], '<strong>Question :</strong><br>Une seule ligne &lt;test&gt; &amp; suite') && str_contains($field['html'], "Première ligne<br />\nDeuxième ligne"));
$question = mail_content(['Quel est votre objectif ?' => mail_below('Retrouver confiance.')]);
$check('question complète : aucune double ponctuation avant la réponse', $question['text'] === "Quel est votre objectif ?\nRetrouver confiance.\n" && str_contains($question['html'], '<strong>Quel est votre objectif ?</strong><br>Retrouver confiance.'));

$person = ['nom' => 'Exemple', 'prenom' => 'Camille', 'email' => 'camille@example.test', 'telephone' => '+41790000000', 'message' => ''];
$start = new DateTimeImmutable('2026-10-02 12:30', new DateTimeZone('Europe/Zurich'));
foreach (booking_options() as $id => $service) {
    $answers = ['Date de naissance' => '15.03.1990'];
    $fields = intake_definition($service)['fields'];
    foreach ($fields as $key => $definition) {
        $answers[$definition['label']] = isset($definition['options']) ? reset($definition['options']) : match ($key) {
            'situation' => 'Je traverse une période de changement professionnel.',
            'question' => 'Comment aborder cette nouvelle étape avec confiance ?',
            default => 'Réponse personnelle de Camille.',
        };
    }
    $receipt = intake_receipt($service, $person, $answers, $start, $start->modify('+' . $service['duration'] . ' minutes'));
    $original = $receipt;
    $values = person_values($person) + [
        'prestation' => $service['name'], 'date' => date_fr($start), 'tarif' => price_label($service),
        'lien_annulation' => 'https://example.test/annuler.php?r=exemple&s=exemple', 'delai_annulation' => cancel_notice_label(),
    ];
    $ownerDetails = intake_owner_mail_details($service, $receipt);
    $clientDetails = intake_client_mail_details($service, $receipt);
    $owner = mail_template(appointment_text('avis_reservation')['body'], $values, $ownerDetails);
    $client = mail_template(appointment_text($service['service'])['body'], $values, $clientDetails);

    $check("$id : coordonnées avant la séance, séparées par une ligne vide", str_contains($owner['text'], "Nom : Exemple\nPrénom : Camille\nE-mail : camille@example.test\nTéléphone : +41790000000\nDate de naissance : 15.03.1990\n\nPrestation : "));
    foreach ($fields as $definition) {
        $label = $definition['label'];
        $below = ($definition['type'] ?? '') !== 'select';
        $heading = $label . (str_ends_with($label, '?') ? '' : ' :');
        $check("$id : présentation de « $label »", str_contains($owner['text'], $heading . ($below ? "\n" : ' ') . $answers[$label])
            && str_contains($owner['html'], '<strong>' . e($heading) . '</strong>' . ($below ? '<br>' : ' ') . e($answers[$label])));
    }
    $check("$id : deux accords séparés et numérotés", str_contains($owner['text'], "\n\nAccord validé par : Camille Exemple\nAccords cochés :\n1. ") && str_contains($owner['text'], "\n2. ") && !str_contains($owner['text'], "\n3. "));
    $check("$id : longues conditions retirées de l’avis Elodie", !str_contains($owner['text'], 'Prestataire :') && !str_contains($owner['text'], 'Cadre de la prestation :') && !str_contains($owner['text'], 'Données du formulaire :') && !str_contains($owner['text'], 'Annulation et retard :'));
    $check("$id : horodatage et empreinte restent uniquement au dossier", !str_contains($owner['text'] . $client['text'], 'Validation :') && !str_contains($owner['text'] . $client['text'], 'Version des conditions :') && !str_contains($owner['text'] . $client['text'], $receipt['conditions_hash']) && $receipt === $original && isset($receipt['validated_at'], $receipt['version'], $receipt['conditions_hash'], $receipt['privacy']));
    $check("$id : résumé client dans l’ordre du Word", array_keys($clientDetails[0]) === ['Prestataire', 'Date', 'Prestation et tarif', 'Tarif', 'Format']);
    $previous = 0;
    foreach (['Réservation et paiement', 'Cadre de la prestation', 'Annulation et retard'] as $label) {
        $position = strpos($client['text'], "\n\n$label :\n" . $receipt['conditions'][$label]);
        $check("$id : paragraphe client « $label »", $position !== false && $position > $previous && str_contains($client['html'], '<strong>' . e($label) . ' :</strong><br>'));
        $previous = $position ?: 0;
    }
    $check("$id : validation en ligne dans un paragraphe distinct", str_contains($client['text'], "\n\nValidation en ligne : " . $receipt['conditions']['Validation en ligne']));
    $check("$id : questionnaire et blocs absents du Word retirés du client", !str_contains($client['text'], 'Date de naissance :') && !str_contains($client['text'], 'Accords cochés :') && !str_contains($client['text'], 'Confidentialité des échanges :') && !str_contains($client['text'], 'Données du formulaire :') && !str_contains($client['text'], 'Réponse personnelle'));
    $check("$id : lien d’annulation conservé", str_contains($client['html'], '<a href="https://example.test/annuler.php?r=exemple&amp;s=exemple">annuler mon rendez-vous</a>'));
    $restored = json_decode(json_encode($ownerDetails, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    $check("$id : mise en forme conservée après stockage et reprise", mail_template(appointment_text('avis_reservation')['body'], $values, $restored) === $owner);
    if ($service['service'] === 'coaching') {
        $check("$id : conditions propres au coaching conservées", str_contains($client['text'], "Organisation de l’accompagnement :\n") && str_contains($client['text'], "Fin de l’accompagnement :\n") && !str_contains($client['text'], 'Questions non traitées :'));
    }
    if ($id === 'tirage') {
        $check('tirage : type de tirage juste après la prestation', str_contains($owner['text'], "Prestation : Tirage de cartes (Par message)\nType de tirage souhaité : 1 carte · message du moment\nDate : "));
        $preview('elodie', 'Tirage de cartes — Elodie', $owner);
        $preview('client', 'Tirage de cartes — Client', $client);
    }
    $preview($id . '-elodie', $service['name'] . ' — Elodie', $owner);
    $preview($id . '-client', $service['name'] . ' — Client', $client);
}

$legacyReceipt = $receipt;
$legacyReceipt['consents'] = ['adult' => 'Ancien accord de majorité.', 'scope' => 'Ancien accord sur le cadre.', 'terms' => 'Ancien accord sur les conditions.', 'consent' => 'Ancien accord sur les données.'];
$legacyOwner = mail_template('{details}', [], intake_owner_mail_details($service, $legacyReceipt));
$check('anciens dossiers : les quatre accords archivés restent lisibles', str_contains($legacyOwner['text'], "1. Ancien accord de majorité.") && str_contains($legacyOwner['text'], "4. Ancien accord sur les données."));

$contactPerson = array_replace($person, ['message' => 'Je souhaite en savoir plus sur vos accompagnements.']);
$contact = mail_template(appointment_text('avis_demande')['body'], person_values($contactPerson), contact_mail_details($contactPerson));
$check('contact : coordonnées puis message sur une nouvelle ligne', str_contains($contact['text'], "Téléphone : +41790000000\n\nMessage :\nJe souhaite en savoir plus") && str_contains($contact['html'], '<strong>Message :</strong><br>Je souhaite en savoir plus'));
$preview('contact-elodie', 'Demande de contact — Elodie', $contact);
$contactClient = mail_template(appointment_text('demande')['body'], person_values($contactPerson));
$check('contact : accusé client en paragraphes, sans reprise du message privé', str_contains($contactClient['text'], "Bonjour Camille,\n\n") && str_contains($contactClient['text'], "intérêt.\n\nVotre demande") && !str_contains($contactClient['text'], $contactPerson['message']) && !str_contains($contactClient['text'], '{details}'));
$preview('contact-client', 'Demande de contact — Client', $contactClient);
$contactPerson['message'] = "Une ligne <script>privée</script>\nDeuxième ligne & suite";
$contact = mail_template(appointment_text('avis_demande')['body'], person_values($contactPerson), contact_mail_details($contactPerson));
$check('contact : sauts saisis conservés et balises neutralisées', str_contains($contact['html'], "Une ligne &lt;script&gt;privée&lt;/script&gt;<br />\nDeuxième ligne &amp; suite") && !str_contains($contact['html'], '<script>'));
$contactPerson['message'] = '';
$check('contact : message facultatif vide présenté clairement', str_contains(mail_template('{details}', [], contact_mail_details($contactPerson))['text'], "Message :\nAucun message"));

$appointment = array_diff_key($person, ['message' => true]) + ['prestation' => 'Séance de pendule', 'start' => $start, 'end' => $start->modify('+20 minutes')];
$cancelValues = person_values($appointment) + ['prestation' => $appointment['prestation'], 'date' => date_fr($start)];
$cancelMails = cancellation_emails($appointment);
$check('annulation : destinataires et adresses de réponse adaptés', count($cancelMails) === 2 && $cancelMails[0][0] === $person['email'] && $cancelMails[0][2] === config('site.email') && $cancelMails[1][0] === config('mail_to') && $cancelMails[1][2] === $person['email']);
foreach ($cancelMails as [$to, $key, $replyTo, $details]) {
    $content = mail_template(appointment_text($key)['body'], $cancelValues, $details);
    $check("$key : prestation, plage horaire et heure suisse présentes", str_contains($content['text'], "Prestation : Séance de pendule\nDate : vendredi 2 octobre 2026, de 12h30 à 12h50 (heure suisse, Europe/Zurich)"));
    $check("$key : aucun accord, condition ni message personnel superflu", !str_contains($content['text'], 'Accords cochés') && !str_contains($content['text'], 'Version des conditions') && !str_contains($content['text'], 'Message :'));
    if ($key === 'avis_annulation') {
        $check('annulation Elodie : coordonnées avant la séance', str_contains($content['text'], "Téléphone : +41790000000\n\nPrestation : "));
    } else {
        $check('annulation client : notification courte sans coordonnées répétées', !str_contains($content['text'], 'E-mail :') && str_contains($content['text'], "Bonjour Camille,\n\nVotre rendez-vous est bien annulé."));
    }
    $preview($key === 'annulation' ? 'annulation-client' : 'annulation-elodie', 'Annulation — ' . ($key === 'annulation' ? 'Client' : 'Elodie'), $content);
}
if ($previews) {
    $links = '';
    foreach ($previews as $name => $title) {
        if (in_array($name, ['elodie', 'client'], true)) continue;
        $links .= '<li><a href="' . e($name) . '.html">' . e($title) . '</a></li>';
    }
    file_put_contents(ROOT_DIR . '/storage/mail-preview/index.html', '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Aperçus des e-mails</title><body style="font:16px/1.6 Arial,sans-serif;margin:24px"><h1>Aperçus des e-mails</h1><p>Exemples fictifs, sans envoi.</p><ul>' . $links . '</ul></body></html>');
}
echo $failures ? "\n$failures échec(s)\n" : "\nTous les tests de mise en forme des e-mails passent.\n";
exit($failures ? 1 : 0);
