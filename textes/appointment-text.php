<?php
// Une ligne vide sépare deux paragraphes, un retour à la ligne reste un retour à la ligne.
// {details} : récapitulatif en gras. Mots remplacés : {prenom} {nom} {telephone} {telephone_elodie},
// et pour une réservation {prestation} {date} {tarif} {lien_annulation} {delai_annulation} (« 24 h », réglé dans app/config.php).
// [texte](lien) devient un lien cliquable sur « texte ».

return [

    'tirage' => [
        'subject' => 'Votre tirage de cartes est confirmé : {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre tirage de cartes est confirmé, je me réjouis de ce moment avec vous.

            {details}

            Je vous écrirai à l'heure prévue, au numéro que vous m'avez indiqué. Si vous le souhaitez, notez d'ici là la question ou la thématique que vous aimeriez explorer.

            Pour annuler, utilisez ce lien jusqu'à {delai_annulation} avant le rendez-vous : [annuler mon rendez-vous]({lien_annulation})
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'pendule' => [
        'subject' => 'Votre séance de pendule est confirmée : {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre séance de pendule est confirmée, je me réjouis de ce moment avec vous.

            {details}

            Je vous écrirai à l'heure prévue, au numéro que vous m'avez indiqué. Le pendule répond au mieux à des questions claires : notez celles que vous aimeriez éclaircir.

            Pour annuler, utilisez ce lien jusqu'à {delai_annulation} avant le rendez-vous : [annuler mon rendez-vous]({lien_annulation})
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'coaching' => [
        'subject' => 'Votre séance de coaching spirituel est confirmée : {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre séance de coaching spirituel est confirmée, je me réjouis de ce moment avec vous.

            {details}

            La séance a lieu en visio : je vous transmets les informations de connexion avant le rendez-vous. Prévoyez un endroit calme où vous vous sentez bien.

            Pour annuler, utilisez ce lien jusqu'à {delai_annulation} avant le rendez-vous : [annuler mon rendez-vous]({lien_annulation})
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'demande' => [
        'subject' => 'Votre demande est bien arrivée',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Merci pour votre message, il est bien arrivé. Je vous réponds sous 48 h pour convenir ensemble d'un moment.

            {details}

            Si c'est urgent, vous pouvez aussi m'envoyer un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    // Envoyé quand la personne annule avec le lien de sa confirmation ({tarif} et les liens n'y sont pas).
    'annulation' => [
        'subject' => 'Votre rendez-vous du {date} est annulé',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre rendez-vous est bien annulé.

            {details}

            Si vous souhaitez choisir un autre moment, vous pouvez réserver à nouveau sur le site ou m'envoyer un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    // E-mails reçus par Elodie. {details} y reprend toutes les informations, message compris.
    'avis_reservation' => [
        'subject' => 'Nouveau rendez-vous : {prestation}, {date}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} a réservé un rendez-vous depuis le site.

            {details}
            TEXTE,
    ],

    'avis_annulation' => [
        'subject' => 'Rendez-vous annulé : {prestation}, {date}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} a annulé son rendez-vous depuis le site. Il est retiré de l'agenda.

            {details}
            TEXTE,
    ],

    'avis_demande' => [
        'subject' => 'Formulaire de contact de {nom} {prenom}',
        'body'    => <<<'TEXTE'
            {details}
            TEXTE,
    ],

];
