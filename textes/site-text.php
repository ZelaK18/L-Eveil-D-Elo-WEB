<?php
/*
 * TEXTES DU SITE, dans l'ordre de la page.
 *
 * Comment modifier :
 * - Changez seulement ce qui est entre guillemets "…". Gardez les guillemets, les virgules et les crochets [ ].
 * - Pour des guillemets à l'intérieur d'un texte, utilisez « ». N'utilisez pas le signe $.
 * - **mot** s'affiche en gras, *mot* en italique (sauf dans les MESSAGES, affichés tels quels).
 * - Enregistrez, puis rechargez la page du site pour voir le résultat.
 * - En cas de faute de frappe, le site garde automatiquement la dernière version qui fonctionnait.
 *
 * Le téléphone, l'e-mail et le lien Instagram se règlent dans app/config.php.
 */

return [

    // MENU (en haut de la page et en bas de page)
    'menu' => [
        'accueil'      => "Accueil",
        'prestations'  => "Prestations",
        'bons_cadeaux' => "Bons cadeaux",
        'contact'      => "Contact",
        'rendez_vous'  => "Rendez-vous",
    ],

    // ACCUEIL (le haut de la page)
    'accueil' => [
        'surtitre'           => "Tirage de cartes · Pendule · Coaching spirituel",
        'titre_ligne_1'      => "Écoutez ce qui",
        'titre_ligne_2'      => "*s'éveille* en vous",
        'texte'              => "Je vous accueille à distance, dans un espace doux et sans jugement, où l'on prend le temps de déposer les questions qui pèsent et d'écouter les réponses qui, souvent, sont déjà là.",
        'bouton_rendez_vous' => "Prendre rendez-vous",
        'bouton_prestations' => "Découvrir les prestations",
        'valeurs'            => ["À l'écoute", "Guidée par l'intuition", "En toute confidentialité"],
        'badge_photo'        => "À distance & en visio",
        'description_photo'  => "Elodie Fauquex, coach en spiritualité",
    ],

    // QUI SUIS-JE
    'qui_suis_je' => [
        'surtitre'    => "Qui suis-je",
        'nom'         => "Elodie",
        'role'        => "Coach en spiritualité",
        'paragraphes' => [
            "Depuis toujours, je ressens ce qui ne se dit pas. Pendant longtemps j'ai mis cette sensibilité de côté, jusqu'au jour où elle s'est imposée à moi comme une évidence : elle n'était pas un poids, mais un outil.",
            "Je me suis alors formée au tirage de cartes et au travail au pendule, et j'ai appris, séance après séance, à mettre cette écoute au service des autres. **L'éveil d'Elo** est né de ce cheminement.",
            "Mon rôle n'est pas de décider à votre place ni de prédire un avenir figé. Il est de vous offrir un miroir bienveillant, d'éclairer ce qui est encore flou et de vous rendre votre pouvoir de choisir.",
        ],
        'signature'   => "Au plaisir de vous rencontrer, Elodie",
    ],

    // PRESTATIONS
    'prestations' => [
        'surtitre'      => "Ce que je propose",
        'titre'         => "Prestations",
        'texte'         => "Chaque accompagnement est unique et s'adapte à ce que vous traversez. Si vous hésitez entre deux formules, écrivez-moi : nous choisirons ensemble.",
        'lien_reserver' => "Réserver",
        'a_venir'       => "À venir",
        'bientot'       => "Cette prestation sera bientôt disponible.",

        // Une carte par prestation.
        // duree : en minutes (sert aussi au calendrier de réservation). prix : en francs, sans « CHF ».
        // disponible : true = réservable, false = affichée « À venir ».
        'cartes' => [
            'tirage' => [
                'nom'        => "Tirage de cartes",
                'texte'      => "Un temps de guidance autour d'une question qui vous occupe : une relation, un choix professionnel, une période de transition. Les cartes sont tirées puis interprétées avec sensibilité, comme une conversation plutôt qu'un verdict.",
                'points'     => ["Question libre ou thématique", "Lecture commentée et échange", "Récapitulatif écrit sur demande"],
                'duree'      => 45,
                'prix'       => 60,
                'format'     => "Par téléphone",
                'disponible' => true,
            ],
            'pendule' => [
                'nom'        => "Pendule",
                'texte'      => "Le pendule répond là où le mental tourne en rond. Utile pour clarifier une hésitation, faire le tri dans vos ressentis ou vérifier ce que votre intuition vous souffle déjà tout bas.",
                'points'     => ["Réponses claires et ciblées", "Idéal en complément d'un tirage", "Compte rendu à la fin de la séance"],
                'duree'      => 45,
                'prix'       => 45,
                'format'     => "Par téléphone",
                'disponible' => true,
            ],
            'coaching' => [
                'nom'        => "Coaching spirituel",
                'texte'      => "Un accompagnement sur plusieurs séances pour avancer en profondeur : reprendre confiance, poser des limites, écouter votre intuition au quotidien et remettre du sens là où il s'est perdu.",
                'points'     => ["Séance découverte sans engagement", "Suivi personnalisé, à votre rythme", "Exercices doux entre les rendez-vous"],
                'duree'      => 60,
                'prix'       => 80,
                'prix_par'   => "séance",
                'format'     => "En visio",
                'disponible' => true,
            ],
            'reiki' => [
                'nom'        => "Reiki",
                'texte'      => "Un soin énergétique par apposition des mains, habillé et allongé confortablement. Le Reiki apaise le système nerveux, relâche les tensions accumulées et réharmonise la circulation de l'énergie dans le corps.",
                'points'     => ["Séance en silence, sans manipulation", "Détente profonde et regain d'énergie", "Idéal en période de fatigue ou de stress"],
                'disponible' => false,
            ],
        ],
    ],

    // BONS CADEAUX
    'bons_cadeaux' => [
        'surtitre'    => "Faire plaisir",
        'titre'       => "Bons cadeaux",
        'paragraphes' => [
            "Offrir un bon cadeau, c'est offrir une parenthèse : un moment rien qu'à soi, pour souffler et y voir plus clair.",
            "Valable sur toutes les prestations, pendant 12 mois.",
        ],
        'etapes' => [
            ['titre' => "Vous choisissez", 'texte' => "Une prestation précise ou un montant libre."],
            ['titre' => "Je crée le bon", 'texte' => "Personnalisé avec le prénom et votre petit mot."],
            ['titre' => "Vous l'offrez", 'texte' => "Reçu par e-mail en PDF, ou imprimé sur beau papier."],
        ],
        'bouton'         => "Commander un bon cadeau",
        'image_titre'    => "Bon cadeau",
        'image_texte'    => "Une séance au choix",
        'image_pour'     => "Pour :",
        'image_validite' => "Valable 12 mois",
    ],

    // CONTACT
    'contact' => [
        'surtitre'        => "Parlons-en",
        'titre'           => "Contact",
        'texte'           => "Une question avant de réserver ? Un doute sur la prestation qui vous correspond ? Écrivez-moi, je réponds sous 48 h.",
        'email_texte'     => "Écrire un message",
        'telephone_texte' => "Du lundi au samedi, 9h à 19h",
        'instagram_titre' => "Instagram",
        'instagram_texte' => "Tirages du mois & guidances",
    ],

    // PRENDRE RENDEZ-VOUS (réservation en ligne et formulaire de demande)
    'rendez_vous' => [
        'surtitre' => "Réserver",
        'titre'    => "Prendre rendez-vous",
        'texte'    => "Deux façons de convenir d'un moment ensemble : choisissez celle qui vous ressemble le plus.",
        'ou'       => "ou",

        'en_ligne_titre'        => "Réserver en ligne",
        'en_ligne_texte'        => "Choisissez directement un créneau libre dans mon agenda. La confirmation vous parvient aussitôt par e-mail.",
        'etape_prestation'      => "La prestation",
        'etape_date'            => "Le jour et l'heure",
        'champ_message'         => "Un mot avant la séance",
        'champ_message_exemple' => "Facultatif",
        'accord'                => "J'accepte que ces informations soient enregistrées dans l'agenda pour organiser le rendez-vous",
        'bouton'                => "Confirmer le rendez-vous",
        'confirme_titre'        => "C'est noté !",
        'bouton_autre'          => "Réserver un autre moment",

        'demande_titre'             => "Faire une demande",
        'demande_texte'             => "Dites-moi ce qui vous amène, je vous propose un créneau par retour de message.",
        'demande_choix'             => "Votre demande",
        'demande_bon_cadeau'        => "Bon cadeau",
        'demande_bon_cadeau_format' => "Format papier ou PDF",
        'demande_format'            => "Format",
        'demande_format_vide'       => "Selon la prestation choisie",
        'demande_message'           => "Votre message",
        'demande_message_exemple'   => "Ce qui vous amène, une question…",
        'demande_accord'            => "J'accepte que ces informations soient utilisées pour me recontacter",
        'demande_bouton'            => "Envoyer ma demande",
    ],

    // CHAMPS COMMUNS AUX DEUX FORMULAIRES
    'formulaire' => [
        'nom'                  => "Nom",
        'prenom'               => "Prénom",
        'email'                => "E-mail",
        'telephone'            => "Téléphone",
        'lien_confidentialite' => "politique de confidentialité",
    ],

    // BAS DE PAGE
    'pied_de_page' => [
        'avertissement'    => "Les séances proposées relèvent du bien-être et ne remplacent en aucun cas un avis ou un suivi médical.",
        'droits'           => "Tous droits réservés",
        'mentions_legales' => "Mentions légales",
        'confidentialite'  => "Politique de confidentialité",
    ],

    // MESSAGES affichés pendant l'utilisation des formulaires (sans gras ni italique).
    // Dans reservation_confirmee, {prestation}, {date} et {email} sont remplacés automatiquement.
    'messages' => [
        'champs_obligatoires'             => "Merci de compléter les champs obligatoires.",
        'envoi_en_cours'                  => "Envoi en cours…",
        'demande_envoyee'                 => "Merci ! Votre demande est bien partie, une confirmation vient de vous être envoyée par e-mail. Je vous réponds sous 48 h.",
        'envoi_echoue'                    => "L'envoi a échoué. Vous pouvez m'écrire directement par e-mail ou par téléphone.",
        'recherche'                       => "Recherche des disponibilités…",
        'aucun_creneau'                   => "Plus aucun créneau libre ce mois-ci : essayez le mois suivant.",
        'agenda_indisponible'             => "L'agenda ne répond pas pour le moment. Réessayez plus tard ou utilisez le formulaire de demande.",
        'reservation_fermee'              => "La réservation en ligne n'est pas encore ouverte. En attendant, le formulaire de demande fonctionne.",
        'choisir_creneau'                 => "Choisissez une prestation, un jour et une heure.",
        'reservation_en_cours'            => "Réservation en cours…",
        'reservation_echouee'             => "La réservation n'a pas pu aboutir. Réessayez dans un instant ou utilisez le formulaire de demande.",
        'creneau_pris'                    => "Ce créneau vient d'être pris. Choisissez-en un autre.",
        'reservation_confirmee'           => "Votre rendez-vous « {prestation} » est confirmé pour le {date}. Une confirmation vient de partir à {email}.",
        'reservation_confirmee_telephone' => "Je vous appellerai au numéro indiqué.",
        'email_invalide'                  => "L'adresse e-mail ne semble pas valide.",
        'nom_invalide'                    => "Merci d'indiquer un nom et un prénom valides.",
        'telephone_invalide'              => "Le numéro de téléphone ne semble pas valide.",
        'trop_envois'                     => "Trop d'envois en peu de temps. Réessayez plus tard ou écrivez-moi directement.",
        'page_expiree'                    => "La page a expiré. Rechargez-la puis réessayez.",
    ],

    // GOOGLE ET PARTAGE (onglet du navigateur, résultats de recherche, aperçu WhatsApp ou Facebook)
    'google' => [
        'titre'         => "Tirage de cartes, pendule et coaching spirituel | L'éveil d'Elo", // 60 caractères au plus, sinon Google coupe
        'description'   => "Tirage de cartes et pendule par téléphone, coaching spirituel en visio. Un accompagnement doux et sans jugement en Suisse romande. Réservation en ligne.", // 155 caractères au plus
        'titre_partage' => "L'éveil d'Elo · Tirage de cartes, pendule et coaching spirituel",
    ],

];
