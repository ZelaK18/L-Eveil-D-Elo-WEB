<?php
// Les secrets (clé des formulaires, identifiants Google) sont dans app/secrets.php.

return [
    'site' => [
        'name'          => "L'éveil d'Elo",
        'url'           => 'https://www.leveildelo.ch/',
        'email'         => 'contact.leveildelo@gmail.com',
        'phone'         => '+41791234567',
        'phone_display' => '079 123 45 67',
        'instagram'     => 'https://instagram.com/leveil.delo',
    ],

    // Boîte qui reçoit les demandes du formulaire et les avis de nouvelle réservation.
    'mail_to' => 'contact.leveildelo@gmail.com',

    // duration : durée en minutes. visio : séance en vidéo (sinon, Elodie appelle au numéro donné).
    'services' => [
        'tirage' => [
            'name'      => 'Tirage de cartes',
            'icon'      => 'ico-cards',
            'summary'   => "Guidance par les cartes autour d'une question ou d'une thématique.",
            'text'      => "Un temps de guidance autour d'une question qui vous occupe : une relation, un choix professionnel, une période de transition. Les cartes sont tirées puis interprétées avec sensibilité, comme une conversation plutôt qu'un verdict.",
            'points'    => ['Question libre ou thématique', 'Lecture commentée et échange', 'Récapitulatif écrit sur demande'],
            'available' => true,
            'duration'  => 45,
            'price'     => 60,
            'format'    => 'Par téléphone',
            'visio'     => false,
        ],
        'pendule' => [
            'name'      => 'Pendule',
            'icon'      => 'ico-pendule',
            'summary'   => 'Guidance au pendule pour clarifier une hésitation.',
            'text'      => 'Le pendule répond là où le mental tourne en rond. Utile pour clarifier une hésitation, faire le tri dans vos ressentis ou vérifier ce que votre intuition vous souffle déjà tout bas.',
            'points'    => ['Réponses claires et ciblées', "Idéal en complément d'un tirage", 'Compte rendu à la fin de la séance'],
            'available' => true,
            'duration'  => 45,
            'price'     => 45,
            'format'    => 'Par téléphone',
            'visio'     => false,
        ],
        'coaching' => [
            'name'       => 'Coaching spirituel',
            'icon'       => 'ico-coaching',
            'summary'    => 'Accompagnement sur plusieurs séances pour avancer en profondeur.',
            'text'       => 'Un accompagnement sur plusieurs séances pour avancer en profondeur : reprendre confiance, poser des limites, écouter votre intuition au quotidien et remettre du sens là où il s\'est perdu.',
            'points'     => ['Séance découverte sans engagement', 'Suivi personnalisé, à votre rythme', 'Exercices doux entre les rendez-vous'],
            'available'  => true,
            'duration'   => 60,
            'price'      => 80,
            'price_unit' => 'séance',
            'format'     => 'En visio',
            'visio'      => true,
        ],
        'reiki' => [
            'name'      => 'Reiki',
            'icon'      => 'ico-reiki',
            'summary'   => 'Soin énergétique par apposition des mains : détente profonde et réharmonisation.',
            'text'      => "Un soin énergétique par apposition des mains, habillé et allongé confortablement. Le Reiki apaise le système nerveux, relâche les tensions accumulées et réharmonise la circulation de l'énergie dans le corps.",
            'points'    => ['Séance en silence, sans manipulation', "Détente profonde et regain d'énergie", 'Idéal en période de fatigue ou de stress'],
            'available' => false,
        ],
    ],

    'booking' => [
        'timezone'    => 'Europe/Zurich',
        'calendar_id' => 'primary',

        // Heures affichées sur la carte du téléphone et données à Google (1 = lundi … 7 = dimanche).
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
        // Une réservation raccourcit, coupe en deux ou supprime la plage dans l'agenda.
        'availability_keyword' => 'Dispo',

        'interval'   => 15, // minutes entre deux heures de début proposées
        'buffer'     => 10, // minutes de pause après chaque rendez-vous et autour des autres événements
        'min_notice' => 2,  // heures minimum avant le rendez-vous
        'max_days'   => 60, // jours à l'avance au maximum
    ],
];
