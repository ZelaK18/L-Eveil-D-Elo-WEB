<?php
// Une ligne vide sépare deux paragraphes, un retour à la ligne reste un retour à la ligne.
// {details} : paragraphes du récapitulatif, avec les intitulés en gras (app/intake.php pour les réservations).
// Mots remplacés : {prenom} {nom} {telephone} {telephone_elodie},
// et pour une réservation {prestation} {date} {tarif} {lien_annulation} {delai_annulation} (« 24 h », réglé dans app/config.php).
// [texte](lien) devient un lien cliquable sur « texte ».

return [

    'tirage' => [
        'subject' => 'Votre tirage de cartes est confirmé pour le {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre tirage de cartes est confirmé. Je me réjouis de vous retrouver prochainement pour votre tirage.

            {details}

            Je vous contacterai à l’heure prévue, au numéro que vous m’avez indiqué. D’ici notre rendez-vous, vous pouvez prendre un moment pour réfléchir à la question ou à la thématique que vous souhaitez aborder lors de votre tirage.

            Vous pouvez [annuler votre rendez-vous]({lien_annulation}) jusqu'à {delai_annulation} avant la séance.
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'pendule' => [
        'subject' => 'Votre séance de pendule est confirmée pour le {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Votre séance de pendule est confirmée. Je me réjouis de ce moment avec vous.

            {details}

            Je vous contacterai à l’heure prévue, au numéro que vous m’avez indiqué. Le pendule répond au mieux à des questions claires, notez celles que vous aimeriez éclaircir.

            Vous pouvez [annuler votre rendez-vous]({lien_annulation}) jusqu'à {delai_annulation} avant la séance.
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'coaching' => [
        'subject' => 'Votre séance de coaching spirituel est confirmée pour le {date}',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Merci pour votre confiance. Votre séance de coaching spirituel est bien réservée et je me réjouis de vous retrouver pour ce moment d’échange et d’accompagnement.

            {details}

            Notre séance se déroulera en visioconférence sur Google Meet. Je vous transmettrai le lien de connexion par e-mail avant notre rendez-vous. Je vous invite simplement à prévoir un endroit calme et confortable, où vous pourrez profiter pleinement de ce moment.

            Vous pouvez [annuler votre rendez-vous]({lien_annulation}) jusqu'à {delai_annulation} avant la séance.
            Pour le déplacer, ou à moins de {delai_annulation}, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    'demande' => [
        'subject' => 'Votre demande est bien arrivée',
        'body'    => <<<'TEXTE'
            Bonjour {prenom},

            Merci pour votre message et pour votre intérêt.

            Votre demande est bien arrivée. Je vous répondrai dans un délai de 24 heures.

            Si vous souhaitez compléter votre demande, répondez simplement à cet e-mail ou envoyez-moi un message au {telephone_elodie}.

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

            Si vous souhaitez choisir un autre moment, vous pouvez réserver à nouveau sur le site, répondre à cet e-mail ou m’envoyer un message au {telephone_elodie}.

            À bientôt,
            Elodie
            TEXTE,
    ],

    // E-mails reçus par Elodie. {details} reprend les coordonnées, les réponses et les accords cochés.
    'avis_reservation' => [
        'subject' => 'Nouveau rendez-vous, {prestation}, {date}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} a réservé un rendez-vous depuis le site.

            Le formulaire de la prestation a été validé. Vous trouverez ses réponses et les accords cochés ci-dessous. Vous pouvez répondre directement à cet e-mail pour joindre cette personne.

            {details}
            TEXTE,
    ],

    'avis_annulation' => [
        'subject' => 'Rendez-vous annulé, {prestation}, {date}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} a annulé son rendez-vous depuis le site. Il est retiré de l'agenda.

            Vous trouverez les coordonnées de cette personne et le rendez-vous annulé ci-dessous. Vous pouvez répondre directement à cet e-mail pour la joindre.

            {details}
            TEXTE,
    ],

    'avis_demande' => [
        'subject' => 'Formulaire de contact de {nom} {prenom}',
        'body'    => <<<'TEXTE'
            {prenom} {nom} vous a envoyé une demande depuis le site.

            Vous trouverez ses coordonnées et son message ci-dessous. Vous pouvez répondre directement à cet e-mail pour joindre cette personne.

            {details}
            TEXTE,
    ],

];
