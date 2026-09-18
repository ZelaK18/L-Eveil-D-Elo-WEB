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
        'titre_ligne_1'      => "Un temps pour vous",
        'titre_ligne_2'      => "*un éclairage* pour avancer",
        'texte'              => "Un accompagnement bienveillant pour accueillir vos questionnements, écouter vos ressentis et vous reconnecter à votre intuition",
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
            "Moi, c’est Elodie. Solaire, intuitive et à l’écoute, la spiritualité fait partie de mon chemin depuis plusieurs années.",
            "Coach spirituelle certifiée et actuellement en apprentissage du Reiki, je vous accompagne avec douceur et sans jugement.",
            "Aujourd’hui, j’ai choisi d’écouter cette petite voix qui m’accompagne depuis quelque temps et de donner vie à **L'éveil d'Elo**, un projet qui me ressemble, porté par l’écoute, le partage et la reconnexion à soi.",
        ],
        'signature'   => "Au plaisir de vous rencontrer, Elodie",
    ],

    // PRESTATIONS
    'prestations' => [
        'surtitre'      => "Ce que je propose",
        'titre'         => "Prestations",
        'texte'         => "Chaque accompagnement est unique et s’adapte à vos besoins du moment. Écoutez simplement ce qui vous appelle, je suis là pour vous guider.",
        'lien_reserver' => "Réserver",
        'a_venir'       => "À venir",
        'bientot'       => "Cette prestation sera bientôt disponible.",
        'offre_fermer'  => "Revenir à la prestation",

        // Une carte par prestation. Chaque valeur modifiée ici se met à jour partout, rien d'autre à changer :
        // - duree : en minutes. Carte, liste de réservation, heures proposées par le calendrier, fin du rendez-vous dans l'agenda.
        // - prix : en francs, sans « CHF ». Carte, liste de réservation, e-mails, agenda, fiche Google.
        // - disponible : true = réservable, false = affichée « À venir ».
        // - offres : facultatif. Une prestation à formules n'a ni duree ni prix : chaque formule a les siens.
        //   Ses formules remplacent les étiquettes en bas de la carte ; cliquer sur un nom ouvre son détail.
        //   duree : texte affiché sur la carte de la formule et dans la liste de réservation (ex. "3 × 60 min").
        //   duree_reservation : en minutes, heures proposées par le calendrier et fin du rendez-vous dans l'agenda
        //   (pour un pack, la durée de la première séance).
        //   tarif : texte affiché tel quel (ex. "Offert", "330 CHF"). Carte, liste de réservation, e-mails, agenda,
        //   et fiche Google (le premier nombre du tarif).
        'cartes' => [
            'tirage' => [
                'nom'        => "Tirage de cartes",
                'texte'      => "À travers les cartes, je vous accompagne dans vos questionnements pour vous aider à prendre du recul, découvrir de nouvelles perspectives et écouter davantage ce que votre intuition vous souffle.",
                'points'     => ["Question libre ou thématique", "Lecture intuitive", "Récapitulatif par écrit"],
                'duree'      => 30,
                'prix'       => 20,
                'format'     => "Par message",
                'disponible' => true,
            ],
            'pendule' => [
                'nom'        => "Pendule",
                'texte'      => "Le pendule est pour moi un outil d’écoute et de guidance. Il nous permet d’explorer ensemble une question précise et de clarifier vos ressentis.",
                'points'     => ["Réponse claire et ciblée", "Idéal en complément d'un tirage",],
                'duree'      => 10,
                'prix'       => 5,
                'format'     => "Par message",
                'disponible' => true,
            ],
            'coaching' => [
                'nom'        => "Coaching spirituel",
                'texte'      => "Besoin de faire le point, de retrouver confiance ou simplement de vous reconnecter à vous-même ? Je vous accompagne pour poser un regard différent sur ce que vous traversez et retrouver vos propres repères.",
                'points'     => ["Séance découverte sans engagement", "Suivi personnalisé, à votre rythme", "Exercices entre les rendez-vous"],
                'format'     => "En visio",
                'disponible' => true,
                'offres' => [
                    [
                        'nom'               => "Premier pas vers Soi",
                        'duree'             => "30 min",
                        'duree_reservation' => 30,
                        'finalite'          => "Faire connaissance, clarifier la demande et vérifier si le coaching est adapté.",
                        'tarif'             => "Offert",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Séance individuelle",
                        'duree'             => "60 min",
                        'duree_reservation' => 60,
                        'finalite'          => "Travailler une problématique ciblée et repartir avec une action concrète.",
                        'tarif'             => "100 CHF",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Pack Clarté",
                        'duree'             => "3 × 60 min",
                        'duree_reservation' => 60,
                        'finalite'          => "Comprendre les blocages, retrouver une direction et amorcer le changement.",
                        'tarif'             => "330 CHF",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Revenir à Soi",
                        'duree'             => "6 × 60 min sur 12 semaines",
                        'duree_reservation' => 60,
                        'finalite'          => "Vivre un parcours complet d’alignement, de transformation et d’ancrage.",
                        'tarif'             => "650 CHF",
                        'format'            => "En visio",
                    ],
                ],
            ],
            'reiki' => [
                'nom'        => "Soin énergétique (Reiki)",
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
        'texte'           => "Une question ? Un doute sur la prestation qui vous correspond ? N'hésitez pas à m'écrire.",
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
        'demande_message'           => "Votre message",
        'demande_message_exemple'   => "Ce qui vous amène, une question…",
        // Écrit dans le message quand on clique sur « Commander un bon cadeau ».
        'demande_message_bon_cadeau' => "Bonjour, je souhaite commander un bon cadeau.",
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

    // PAGE D'ANNULATION (ouverte par le lien de l'e-mail de confirmation)
    // {prestation}, {date}, {delai} (« 24 h ») et {telephone} sont remplacés automatiquement.
    'annulation' => [
        'google_titre'  => "Annuler un rendez-vous | L'éveil d'Elo",
        'surtitre'      => "Rendez-vous",
        'titre'         => "Annuler un rendez-vous",
        'confirmer'     => "Vous êtes sur le point d'annuler votre rendez-vous **{prestation}** du **{date}**.",
        'bouton'        => "Annuler ce rendez-vous",
        'garder'        => "Garder mon rendez-vous",
        'annule_titre'  => "Rendez-vous annulé",
        'annule'        => "Votre rendez-vous **{prestation}** du **{date}** est bien annulé. Une confirmation vient de vous être envoyée par e-mail.",
        'bouton_autre'  => "Réserver un autre moment",
        'trop_tard'     => "Votre rendez-vous du **{date}** a lieu dans moins de {delai} : il ne s'annule plus en ligne. Pour l'annuler ou le déplacer, envoyez-moi un message au {telephone}.",
        'passe'         => "Ce rendez-vous est déjà passé.",
        'deja_annule'   => "Ce rendez-vous est déjà annulé.",
        'invalide'      => "Ce lien d'annulation n'est pas valide. Vérifiez qu'il est complet, ou envoyez-moi un message au {telephone}.",
        'erreur'        => "L'annulation n'a pas pu aboutir. Réessayez dans un instant ou envoyez-moi un message au {telephone}.",
        'retour'        => "Retour à l'accueil",
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
        'reservation_confirmee_telephone' => "Je vous écrirai au numéro indiqué à l'heure prévue.",
        'email_invalide'                  => "L'adresse e-mail ne semble pas valide.",
        'nom_invalide'                    => "Merci d'indiquer un nom et un prénom valides.",
        'telephone_invalide'              => "Le numéro de téléphone ne semble pas valide.",
        'trop_envois'                     => "Trop d'envois en peu de temps. Réessayez plus tard ou écrivez-moi directement.",
        'page_expiree'                    => "La page a expiré. Rechargez-la puis réessayez.",
    ],

    // GOOGLE ET PARTAGE (onglet du navigateur, résultats de recherche, aperçu WhatsApp ou Facebook)
    'google' => [
        'titre'         => "Tirage de cartes, pendule et coaching spirituel | L'éveil d'Elo", // 60 caractères au plus, sinon Google coupe
        'description'   => "Tirage de cartes et pendule par message, coaching spirituel en visio. Un accompagnement doux et sans jugement en Suisse romande. Réservation en ligne.", // 155 caractères au plus
        'titre_partage' => "L'éveil d'Elo · Tirage de cartes, pendule et coaching spirituel",
    ],

];
