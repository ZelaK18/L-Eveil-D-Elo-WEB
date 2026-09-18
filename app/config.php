<?php
// Les secrets (clé des formulaires, identifiants Google) sont dans app/secrets.php.
// Les textes du site, dont le nom, le prix et la durée des prestations, sont dans textes/site-text.php.

return [
    'site' => [
        'name'          => "L'éveil d'Elo",
        'owner'         => 'Elodie Fauquex',
        'url'           => 'https://www.leveildelo.ch/',
        'email'         => 'contact.leveildelo@gmail.com',
        'phone'         => '+41791234567',
        'phone_display' => '079 123 45 67',
        'instagram'     => 'https://instagram.com/leveil.delo',
    ],

    // Boîte qui reçoit les demandes du formulaire et les avis de nouvelle réservation.
    'mail_to' => 'contact.leveildelo@gmail.com',

    // Réglages techniques des prestations. summary : description pour Google. visio : séance en vidéo (sinon, Elodie écrit par message au numéro de la personne).
    'services' => [
        'tirage' => [
            'icon'    => 'ico-cards',
            'summary' => "Guidance par les cartes autour d'une question ou d'une thématique.",
            'visio'   => false,
        ],
        'pendule' => [
            'icon'    => 'ico-pendule',
            'summary' => 'Guidance au pendule pour clarifier une hésitation.',
            'visio'   => false,
        ],
        'coaching' => [
            'icon'    => 'ico-coaching',
            'summary' => 'Accompagnement sur plusieurs séances pour avancer en profondeur.',
            'visio'   => true,
        ],
        'reiki' => [
            'icon'    => 'ico-reiki',
            'summary' => 'Soin énergétique par apposition des mains : détente profonde et réharmonisation.',
            'visio'   => false,
        ],
    ],

    'booking' => [
        'timezone'    => 'Europe/Zurich',
        'calendar_id' => 'primary',

        // Heures données à Google (1 = lundi … 7 = dimanche).
        // Si availability_keyword est vide, ce sont aussi les heures de réservation, moins les événements « occupés ».
        'hours' => [
            1 => [['09:00', '19:00']],
            2 => [['09:00', '19:00']],
            3 => [['09:00', '19:00']],
            4 => [['09:00', '19:00']],
            5 => [['09:00', '19:00']],
            6 => [['09:00', '19:00']],
            7 => [],
        ],

        // Les créneaux ne s'ouvrent que dans les événements Google Agenda sans titre ou dont le titre contient ce mot
        // (ex. « Dispo » le mardi de 14h à 18h, répétable chaque semaine), moins les rendez-vous qui s'y trouvent.
        // Une réservation raccourcit, coupe en deux ou supprime la plage dans l'agenda ; une annulation en ligne la rend.
        'availability_keyword' => 'Dispo',

        'interval'   => 15, // minutes entre deux heures de début proposées
        'buffer'     => 10, // minutes de pause après chaque rendez-vous et autour des autres événements
        'min_notice' => 2,  // heures minimum avant le rendez-vous
        'max_days'   => 60, // jours à l'avance au maximum

        // Heures minimum avant le rendez-vous pour l'annuler avec le lien de l'e-mail de confirmation.
        // Plus tard, la page d'annulation invite à écrire à Elodie.
        'cancel_notice' => 24,
    ],
];
