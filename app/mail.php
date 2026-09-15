<?php

// Blocs : une chaîne est un paragraphe, un tableau ['Libellé' => 'valeur'] donne des lignes aux libellés en gras.
function mail_content(string|array ...$blocks): array
{
    $text = [];
    $html = '';
    foreach ($blocks as $block) {
        if (is_string($block)) {
            $text[] = $block;
            $html .= '<p>' . nl2br(e($block)) . '</p>';
            continue;
        }

        $lines = $rows = [];
        foreach ($block as $label => $value) {
            $value = (string) $value;
            $separator = str_contains($value, "\n") ? "\n" : ' ';
            $lines[] = "$label :$separator$value";
            $rows[] = '<strong>' . e($label) . ' :</strong>' . ($separator === ' ' ? ' ' : '<br>') . nl2br(e($value));
        }
        $text[] = implode("\n", $lines);
        $html .= '<p>' . implode('<br>', $rows) . '</p>';
    }

    return [
        'text' => implode("\n\n", $text) . "\n",
        'html' => '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#222">' . $html . '</div>',
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

// Paragraphes séparés par une ligne vide ; la ligne {details} devient le récapitulatif.
function mail_template(string $template, array $values, array $details = []): array
{
    $blocks = [];
    foreach (preg_split('/\R[ \t]*\R/', trim($template)) as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '{details}' && $details) {
            $blocks[] = $details;
        } elseif ($paragraph !== '' && $paragraph !== '{details}') {
            $blocks[] = fill_placeholders($paragraph, $values);
        }
    }
    return mail_content(...$blocks);
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
