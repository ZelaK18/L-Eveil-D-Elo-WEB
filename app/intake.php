<?php

function intake_texts(): array
{
    return texts_file('booking-forms');
}

function intake_definition(array $service): array
{
    return intake_texts()['services'][$service['service']] ?? [];
}

// La même version est affichée, vérifiée côté serveur et jointe aux confirmations.
function booking_terms(array $service): array
{
    $definition = intake_definition($service);
    $terms = [
        'Prestataire' => config('site.owner') . ' · ' . config('site.name') . ' · ' . config('site.email'),
        'Prestation et tarif' => $service['name'] . ' · ' . ($service['offer_duration'] ?? duration_label($service['duration'])) . ' · ' . price_label($service) . ' · ' . $service['format'] . '.',
        'Cadre de la prestation' => $definition['scope'] ?? '',
    ];
    if ($service['service'] !== 'coaching') {
        $terms['Questions non traitées'] = 'Mort, diagnostic ou maladie ; vie privée d’un tiers ; contrôle d’une personne ; jeux d’argent ou investissements risqués ; urgence ou danger immédiat ; demande de certitude absolue sur l’avenir.';
    } else {
        $terms['Organisation de l’accompagnement'] = 'Le créneau choisi réserve une séance. Pour un pack ou un programme, il s’agit de la première séance ; les suivantes seront convenues avec Elodie. Le tarif affiché pour un pack ou un programme correspond à l’ensemble de la formule. La séance découverte offerte n’engage à aucun accompagnement payant.';
        $terms['Fin de l’accompagnement'] = 'Chaque partie peut demander l’arrêt de l’accompagnement à tout moment. Les prestations déjà réalisées ou dues sont traitées selon les conditions convenues, sous réserve des dispositions impératives applicables. Le sort des séances non réalisées et de tout solde versé est convenu avec Elodie selon ces dispositions.';
    }
    $terms['Réservation et paiement'] = 'Le rendez-vous est confirmé après validation de ce formulaire et vérification du créneau disponible. Aucun paiement n’est encaissé sur ce site. Le mode et l’échéance de règlement sont convenus directement avec Elodie ; la confirmation du rendez-vous ne vaut pas reçu de paiement.';
    $terms['Annulation et retard'] = 'Toute annulation ou demande de report doit être communiquée au moins ' . cancel_notice_label() . ' avant la séance. Passé ce délai, la séance peut être facturée ou déduite du programme, sauf situation exceptionnelle acceptée par Elodie et sous réserve des dispositions impératives applicables. Une séance offerte reste gratuite. En cas de retard, la séance se termine à l’heure initialement prévue. Le lien de confirmation permet l’annulation en ligne dans le délai prévu ; pour un report, contactez Elodie.';
    $terms['Confidentialité des échanges'] = 'Les échanges sont traités avec confidentialité dans les limites de la loi. Aucun enregistrement audio ou vidéo n’est réalisé sans accord préalable distinct. Les destinataires techniques et les modalités de traitement des formulaires sont précisés ci-dessous.';
    $terms['Données du formulaire'] = intake_texts()['privacy'];
    $terms['Validation en ligne'] = 'Votre nom, votre prénom, vos cases cochées et la date et l’heure de validation sont conservés avec la version des conditions acceptées. Une copie des conditions est envoyée dans votre confirmation. Aucun document à imprimer ni signature manuscrite ne sont demandés.';
    return $terms;
}

function booking_terms_version(array $service): string
{
    return hash('sha256', json_encode([
        intake_texts()['version'], intake_definition($service), booking_terms($service),
        intake_texts()['consents'], texts_file('mentions-legales')['confidentialite'],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

// Fonction sans effets de bord : aucun agenda ni e-mail tant que tout n'est pas valide.
function validate_intake(array $service, array $post, ?DateTimeImmutable $today = null): array
{
    $definition = intake_definition($service);
    if (!$definition) {
        throw new InvalidArgumentException('Le formulaire de cette prestation n’est pas disponible. Contactez Elodie.');
    }
    if (!is_string($post['conditions_version'] ?? null) || !hash_equals(booking_terms_version($service), $post['conditions_version'])) {
        throw new InvalidArgumentException('Les conditions ou les tarifs ont changé. Rechargez la page pour les relire avant de réserver.');
    }
    foreach (intake_texts()['consents'] as $key => $label) {
        if (($post[$key] ?? null) !== '1') {
            throw new InvalidArgumentException('Merci de lire et de cocher chacun des accords avant de confirmer le rendez-vous.');
        }
    }
    $birth = $post['naissance'] ?? null;
    $tz = new DateTimeZone(config('booking.timezone'));
    $today ??= new DateTimeImmutable('today', $tz);
    $date = is_string($birth) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $birth)
        ? DateTimeImmutable::createFromFormat('!Y-m-d', $birth, $tz) : false;
    if (!$date || $date->format('Y-m-d') !== $birth || $date > $today || $date->format('Y') < '1900') {
        throw new InvalidArgumentException('Merci d’indiquer une date de naissance valide.');
    }
    if ($date->diff($today)->y < 18) {
        throw new InvalidArgumentException('La réservation en ligne est réservée aux personnes majeures.');
    }
    $answers = ['Date de naissance' => $date->format('d.m.Y')];
    $fields = $post['questionnaire'] ?? [];
    if (!is_array($fields)) {
        throw new InvalidArgumentException('Merci de compléter le formulaire de la prestation.');
    }
    foreach ($definition['fields'] as $key => $field) {
        $value = $fields[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException('Réponse invalide : ' . $field['label']);
        }
        $value = trim(mb_scrub($value, 'UTF-8'));
        if ($value === '' && $field['required']) {
            throw new InvalidArgumentException('Merci de compléter : ' . $field['label']);
        }
        if (mb_strlen($value) > ($field['max'] ?? 2000)) {
            throw new InvalidArgumentException('Réponse trop longue : ' . $field['label']);
        }
        if (($field['type'] ?? '') === 'select' && $value !== '') {
            if (!isset($field['options'][$value])) {
                throw new InvalidArgumentException('Choix invalide : ' . $field['label']);
            }
            $value = $field['options'][$value];
        }
        $answers[$field['label']] = $value !== '' ? $value : 'Non renseigné';
    }
    return $answers;
}

function intake_receipt(array $service, array $person, array $answers, DateTimeImmutable $start, DateTimeImmutable $end): array
{
    $at = new DateTimeImmutable('now', new DateTimeZone(config('booking.timezone')));
    return [
        'validated_at' => $at->format(DATE_RFC3339),
        'person' => $person,
        'answers' => $answers,
        'appointment' => [
            'Prestation' => $service['name'], 'Date' => period_fr($start, $end) . ' (heure suisse, Europe/Zurich)',
            'Tarif' => price_label($service), 'Format' => $service['format'],
        ],
        'version' => intake_texts()['version'],
        'conditions_hash' => booking_terms_version($service),
        'conditions' => booking_terms($service),
        'privacy' => texts_file('mentions-legales')['confidentialite'],
        'consents' => intake_texts()['consents'],
    ];
}

function intake_agreement_details(array $receipt): array
{
    return [
        'Accord validé par' => $receipt['person']['prenom'] . ' ' . $receipt['person']['nom'],
        'Validation' => (new DateTimeImmutable($receipt['validated_at']))->format('d.m.Y à H:i:s P') . ' (Europe/Zurich)',
        'Version des conditions' => $receipt['version'] . ' · ' . $receipt['conditions_hash'],
        'Accords cochés' => implode("\n", array_values($receipt['consents'])),
    ] + $receipt['conditions'];
}

function deliver_booking_emails(string $recordName, array &$record): void
{
    foreach ($record['emails'] as $index => [$to, $key, $replyTo, $details]) {
        if (!empty($record['sent'][$index])) {
            continue;
        }
        try {
            send_text_mail($to, $key, $record['values'], $details, $replyTo);
            $record['sent'][$index] = true;
        } catch (Throwable $e) {
            $record['sent'][$index] = false;
            error_log("Confirmation $recordName, envoi $index à reprendre : " . $e->getMessage());
        }
        storage_write($recordName, $record);
    }
}
