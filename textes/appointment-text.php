<?php
// Une ligne vide sépare deux paragraphes, un retour à la ligne reste un retour à la ligne.
// {details} : récapitulatif en gras. Mots remplacés : {prenom} {nom} {prestation} {telephone} {telephone_elodie},
// et pour une réservation {date} {tarif}.

return [

    'tirage' => [
        'subject' => 'Votre tirage de cartes est confirmé : {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre tirage de cartes est confirmé, je me réjouis de ce moment avec vous.

            {details}

            Je vous appellerai à l'heure prévue. Si vous le souhaitez, notez d'ici là la question ou la thématique que vous aimeriez explorer.

            Pour déplacer ou annuler, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

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

            Je vous appellerai à l'heure prévue. Le pendule répond au mieux à des questions claires : notez celles que vous aimeriez éclaircir.

            Pour déplacer ou annuler, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

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

            Pour déplacer ou annuler, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

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

    // E-mails reçus par Elodie. {details} y reprend toutes les informations, message compris.
    'avis_reservation' => [
        'subject' => 'Nouveau rendez-vous : {prestation}, {date}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} a réservé un rendez-vous depuis le site.

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
