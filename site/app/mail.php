<?php

const MAIL_LINK = '/\[([^\]\n]+)\]\((https?:\/\/[^\s)]+)\)/u';

// Force une réponse sous son libellé, même si elle ne contient aucun retour à la ligne.
// Un tableau simple reste compatible avec les e-mails conservés pour une reprise d'envoi.
function mail_below(string $text): array
{
    return ['text' => $text, 'below' => true];
}

// Blocs : une chaîne est un paragraphe ; un tableau associe chaque libellé à un texte ou à mail_below().
// [texte](https://…) dans un paragraphe devient un lien sur « texte » (l'adresse seule dans la version texte).
function mail_content(string|array ...$blocks): array
{
    $text = [];
    $html = '';
    foreach ($blocks as $block) {
        if (is_string($block)) {
            $text[] = preg_replace(MAIL_LINK, '$2', $block);
            $html .= '<p style="margin:0 0 16px">' . nl2br(preg_replace(MAIL_LINK, '<a href="$2">$1</a>', e($block))) . '</p>';
            continue;
        }

        $lines = $rows = [];
        foreach ($block as $label => $value) {
            $heading = rtrim($label);
            if (!preg_match('/[?!:]$/u', $heading)) {
                $heading .= ' :';
            }
            $below = is_array($value) && !empty($value['below']);
            $value = str_replace(["\r\n", "\r"], "\n", (string) (is_array($value) ? $value['text'] : $value));
            $separator = $below || str_contains($value, "\n") ? "\n" : ' ';
            $lines[] = $heading . $separator . $value;
            $rows[] = '<strong>' . e($heading) . '</strong>' . ($separator === ' ' ? ' ' : '<br>') . nl2br(e($value));
        }
        $text[] = implode("\n", $lines);
        $html .= '<p style="margin:0 0 16px">' . implode('<br>', $rows) . '</p>';
    }

    return [
        'text' => implode("\n\n", $text) . "\n",
        'html' => '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#222;overflow-wrap:anywhere;word-break:break-word">' . $html . '</div>',
    ];
}

function fill_placeholders(string $text, array $values): string
{
    $pairs = [];
    foreach ($values as $key => $value) {
        $pairs['{' . $key . '}'] = (string) $value;
    }
    return strtr($text, $pairs);
}

// {details} accepte un récapitulatif associatif ou une liste de blocs séparés en paragraphes.
function mail_template(string $template, array $values, array $details = []): array
{
    $blocks = [];
    foreach (preg_split('/\R[ \t]*\R/', trim($template)) as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '{details}' && $details) {
            array_push($blocks, ...(array_is_list($details) ? $details : [$details]));
        } elseif ($paragraph !== '' && $paragraph !== '{details}') {
            $blocks[] = fill_placeholders($paragraph, $values);
        }
    }
    return mail_content(...$blocks);
}

function appointment_text(string $id): array
{
    return texts_file('appointment-text')[$id] ?? [
        'subject' => 'Rendez-vous confirmé : {prestation}, {date}',
        'body'    => "Bonjour {prenom},\n\nVotre rendez-vous est confirmé.\n\n{details}\n\n"
            . "Pour annuler, utilisez ce lien jusqu'à {delai_annulation} avant le rendez-vous : [annuler mon rendez-vous]({lien_annulation})\n"
            . "Pour le déplacer, ou à moins de {delai_annulation}, envoyez-moi un message au {telephone_elodie}.\n\nÀ bientôt,\nElodie",
    ];
}

// Mots remplacés dans tous les e-mails d'une demande ou d'une réservation.
function person_values(array $person): array
{
    return [
        'prenom'           => $person['prenom'],
        'nom'              => $person['nom'],
        'telephone'        => $person['telephone'],
        'telephone_elodie' => config('site.phone_display'),
    ];
}

// Coordonnées communes aux e-mails reçus par Elodie.
function person_details(array $person): array
{
    return [
        'Nom'       => $person['nom'],
        'Prénom'    => $person['prenom'],
        'E-mail'    => $person['email'],
        'Téléphone' => $person['telephone'],
    ];
}

function contact_mail_details(array $person): array
{
    return [person_details($person), [
        'Message' => mail_below($person['message'] !== '' ? $person['message'] : 'Aucun message'),
    ]];
}

// Même ordre que les confirmations : coordonnées de la personne pour Elodie, puis séance concernée.
function cancellation_emails(array $appointment): array
{
    $details = [
        'Prestation' => $appointment['prestation'],
        'Date' => period_fr($appointment['start'], $appointment['end']) . ' (heure suisse, Europe/Zurich)',
    ];
    return [
        [$appointment['email'], 'annulation', config('site.email'), [$details]],
        [config('mail_to'), 'avis_annulation', $appointment['email'], [person_details($appointment), $details]],
    ];
}

// E-mail rédigé dans textes/appointment-text.php, sous la clé $key.
function send_text_mail(string $to, string $key, array $values, array $details = [], ?string $replyTo = null): void
{
    $text = appointment_text($key);
    send_mail($to, fill_placeholders($text['subject'], $values), mail_template($text['body'], $values, $details), $replyTo);
}

// Plusieurs e-mails [destinataire, clé du texte, répondre à, récapitulatif]. L'agenda est déjà à jour :
// un envoi qui échoue est noté dans le journal sans empêcher les autres.
function send_text_mails(array $emails, array $values, string $context): array
{
    $sent = [];
    foreach ($emails as [$to, $key, $replyTo, $details]) {
        try {
            send_text_mail($to, $key, $values, $details, $replyTo);
            $sent[] = true;
        } catch (Throwable $e) {
            $sent[] = false;
            error_log("$context, e-mail à $to : " . $e->getMessage());
        }
    }
    return $sent;
}

// Par Gmail si le compte Google a ce droit, sinon par mail() de l'hébergement.
function send_mail(string $to, string $subject, array $content, ?string $replyTo = null): void
{
    $boundary = 'elo-' . bin2hex(random_bytes(12));
    $body = '';
    foreach (['plain' => $content['text'], 'html' => $content['html']] as $type => $part) {
        $body .= "--$boundary\r\nContent-Type: text/$type; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($part));
    }
    $body .= "--$boundary--\r\n";

    $subject = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    $sender = mb_encode_mimeheader(config('site.name'), 'UTF-8', 'B', "\r\n");
    $headers = ['Reply-To' => $replyTo, 'MIME-Version' => '1.0', 'Content-Type' => "multipart/alternative; boundary=\"$boundary\""];

    if (google_can('gmail.send')) {
        try {
            $account = google_token()['email'] ?? null;
            $lines = ['From' => $account ? "$sender <$account>" : null, 'To' => $to, 'Subject' => $subject] + $headers;
            $mime = '';
            foreach (array_filter($lines) as $name => $value) {
                $mime .= "$name: $value\r\n";
            }
            gmail_send("$mime\r\n$body");
            return;
        } catch (GoogleError $e) {
            error_log('Gmail indisponible, envoi par mail() : ' . $e->getMessage());
        }
    }

    $headers['From'] = $sender . ' <' . config('site.email') . '>';
    if (!mail($to, $subject, $body, array_filter($headers))) {
        throw new RuntimeException("La fonction mail() n'a pas pu envoyer le message.");
    }
}
