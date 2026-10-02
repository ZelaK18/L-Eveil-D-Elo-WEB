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
        'valeurs'            => ["Guidance intuitive", "Connexion", "Authenticité"],
        'badge_photo'        => "À distance & en visio",
        'description_photo'  => "Nuages aux teintes roses et bleues",
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
        // - duree : en minutes. Heures proposées par le calendrier et fin du rendez-vous dans l'agenda.
        // - afficher_duree : false pour garder la durée interne, sans l'afficher aux visiteurs (true par défaut).
        // - prix : en francs, sans « CHF ». Carte, liste de réservation, e-mails, agenda, fiche Google.
        // - disponible : true = réservable, false = affichée « À venir ».
        // - offres : facultatif. Une prestation à formules n'a ni duree ni prix : chaque formule a les siens.
        //   Ses formules remplacent les étiquettes en bas de la carte ; cliquer sur un nom ouvre son détail.
        //   duree : texte affiché sur la carte de la formule et dans la liste de réservation (ex. "3 × 60 min").
        //   duree_reservation : en minutes, heures proposées par le calendrier et fin du rendez-vous dans l'agenda
        //   (pour un pack, la durée de la première séance).
        //   tarif : texte affiché tel quel (ex. "Offert", "120 CHF"). Carte, liste de réservation, e-mails, agenda,
        //   et fiche Google (le premier nombre du tarif).
        //   tarif_habituel : facultatif, affiché en complément du tarif de lancement, sans changer le prix réservé.
        'cartes' => [
            'tirage' => [
                'nom'        => "Tirage de cartes",
                'texte'      => "À travers les cartes, je vous invite à poser un regard différent sur ce que vous traversez. Un moment pour mettre en lumière vos questionnements et laisser émerger de nouvelles pistes.",
                'points'     => ["Une question ou une thématique", "Un récapitulatif écrit de votre tirage"],
                'duree'      => 20,
                'afficher_duree' => false,
                'prix'       => 20,
                'format'     => "Par message",
                'disponible' => true,
            ],
            'pendule' => [
                'nom'        => "Pendule",
                'texte'      => "À travers le pendule, je vous invite à explorer une question précise.",
                'points'     => ["Une réponse claire et ciblée", "Idéal seul ou en complément d’un tirage"],
                'duree'      => 10,
                'afficher_duree' => false,
                'prix'       => 5,
                'format'     => "Par message",
                'disponible' => true,
            ],
            'coaching' => [
                'nom'        => "Coaching spirituel",
                'texte'      => "Parfois, on ressent simplement le besoin de faire une pause et d’y voir plus clair. Je vous accompagne dans ce moment, avec écoute et bienveillance, pour vous aider à retrouver vos propres repères.",
                'points'     => ["Un temps d’échange et d’écoute", "Suivi personnalisé", "Des pistes pour avancer au quotidien"],
                'format'     => "En visio",
                'disponible' => true,
                'offres' => [
                    [
                        'nom'               => "Premier pas vers Soi",
                        'duree'             => "30 min",
                        'duree_reservation' => 30,
                        'finalite'          => "Pour faire connaissance, clarifier votre besoin et vérifier si mon accompagnement vous correspond (recommandé avant chaque début de coaching)",
                        'tarif'             => "Offert",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Élan vers Soi",
                        'duree'             => "60 min",
                        'duree_reservation' => 60,
                        'finalite'          => "Pour travailler sur une situation ou un questionnement précis, avec un exercice personnalisé et une action concrète à intégrer.",
                        'tarif'             => "40 CHF",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Pack Clarté",
                        'duree'             => "3 × 60 min",
                        'duree_reservation' => 60,
                        'finalite'          => "Pour approfondir une problématique et avancer sur plusieurs semaines : trois séances à utiliser sur 6 à 8 semaines, avec exercices entre les rencontres.",
                        'tarif'             => "120 CHF",
                        'format'            => "En visio",
                    ],
                    [
                        'nom'               => "Programme Revenir à Soi",
                        'duree'             => "6 × 60 min",
                        'duree_reservation' => 60,
                        'finalite'          => "Pour vivre un accompagnement plus profond sur 12 semaines : 6 séances de 60 min, exercices personnalisés, temps d’intégration et support bref entre les séances.",
                        'tarif'             => "220 CHF au lancement",
                        'tarif_habituel'    => "280 CHF",
                        'format'            => "En visio",
                    ],
                ],
            ],
            'reiki' => [
                'nom'        => "Soin énergétique (Reiki)",
                'texte'      => "Le Reiki à distance est un moment de détente et de recentrage, à vivre confortablement depuis chez vous.",
                'points'     => ["Un moment de profonde détente", "Un rééquilibrage énergétique en douceur", "Une sensation de calme et d’harmonie intérieure"],
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
        'texte'    => "Réservez en ligne ou écrivez-moi pour trouver un moment ensemble.",
        'ou'       => "ou",

        'en_ligne_titre'        => "Réserver en ligne",
        'en_ligne_texte'        => "Choisissez votre séance et un créneau, puis complétez le formulaire pour confirmer.",
        'etape_prestation'      => "La prestation",
        'etape_date'            => "Le jour et l'heure",
        'sans_javascript'      => "Pour réserver, passez par le formulaire de demande.",
        'reessayer_agenda'     => "Réessayer de charger l’agenda",
        'bouton'                => "Confirmer mon rendez-vous",
        'confirme_titre'        => "C'est noté !",
        'bouton_autre'          => "Réserver un autre moment",

        'demande_titre'             => "Faire une demande",
        'demande_texte'             => "Dites-moi ce qui vous amène, je vous réponds sous 24 h.",
        'demande_message'           => "Votre message",
        'demande_message_exemple'   => "Ce qui vous amène, une question…",
        // Écrit dans le message quand on clique sur « Commander un bon cadeau ».
        'demande_message_bon_cadeau' => "Bonjour, je souhaite commander un bon cadeau.",
        'demande_accord'            => "J’accepte l’utilisation de mes informations pour me répondre",
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

    // PAGE D'ATTENTE (bientot.php)
    'bientot' => [
        'titre_page'        => "Le site arrive bientôt",
        'statut'            => "En cours de création",
        'titre_ligne_1'     => "Le site arrive",
        'titre_ligne_2'     => "bientôt.",
        'description'      => "Un espace tout en douceur se prépare pour vous accompagner, vous inspirer et vous reconnecter à vous-même.",
        'contact'          => "En attendant, je reste à votre écoute sur Instagram.",
        'bouton_instagram' => "Suivre sur Instagram",
        'signature'        => "À très bientôt, Elodie",
    ],

    // PAGE D'ANNULATION (ouverte par le lien de l'e-mail de confirmation)
    // {prestation}, {date}, {delai} (« 24 h ») et {telephone} sont remplacés automatiquement.
    'annulation' => [
        'google_titre'  => "Annuler un rendez-vous | L'éveil d'Elo",
        'surtitre'      => "Votre rendez-vous",
        'titre'         => "Un changement de programme ?",
        'confirmer'     => "Vous souhaitez annuler cette séance ? Confirmez votre choix ci-dessous pour libérer le créneau.",
        'seance'        => "Votre séance",
        'fuseau'        => "Heure suisse",
        'bouton'        => "Annuler ce rendez-vous",
        'garder'        => "Garder mon rendez-vous",
        'annule_titre'  => "Rendez-vous annulé",
        'annule'        => "Votre annulation est bien enregistrée. Une confirmation vient de vous être envoyée par e-mail. Au plaisir de vous retrouver à un autre moment.",
        'annule_sans_email' => "Votre annulation est bien enregistrée, mais la confirmation par e-mail n’a pas pu être envoyée. Contactez Elodie au {telephone} si vous avez besoin d’une confirmation.",
        'bouton_autre'  => "Réserver un autre moment",
        'trop_tard_titre' => "Un imprévu de dernière minute ?",
        'trop_tard'     => "Votre séance a lieu dans **moins de {delai}** : l’annulation en ligne n’est plus disponible. Écrivez-moi directement pour l’annuler ou la déplacer.",
        'passe_titre'   => "Ce rendez-vous est passé",
        'passe'         => "La date de cette séance est passée, il n’y a plus rien à annuler. Vous pouvez choisir un nouveau moment si vous le souhaitez.",
        'deja_annule_titre' => "Ce rendez-vous est déjà annulé",
        'deja_annule'   => "Tout est en ordre, aucune autre démarche n’est nécessaire. Vous pouvez réserver une nouvelle séance quand vous le souhaitez.",
        'invalide_titre' => "Ce lien n’est pas valide",
        'invalide'      => "Le lien semble incomplet ou incorrect. Retrouvez celui de votre e-mail de confirmation, ou écrivez-moi pour que je puisse vous aider.",
        'erreur_titre'  => "L’annulation n’a pas abouti",
        'erreur'        => "Un problème empêche de terminer votre demande. Réessayez dans un instant ou écrivez-moi pour vérifier votre rendez-vous ensemble.",
        'par_sms'       => "Par SMS au",
        'par_mail'      => "ou par mail",
        'retour'        => "Retour à l'accueil",
    ],

    // MESSAGES affichés pendant l'utilisation des formulaires (sans gras ni italique).
    // Dans reservation_confirmee, {prestation}, {date} et {email} sont remplacés automatiquement.
    'messages' => [
        'champs_obligatoires'             => "Merci de compléter les champs obligatoires.",
        'envoi_en_cours'                  => "Envoi en cours…",
        'demande_envoyee'                 => "Merci ! Votre demande est bien partie, une confirmation vient de vous être envoyée par e-mail. Je vous réponds sous 24 h.",
        'demande_sans_email'              => "Votre demande est bien partie, mais la confirmation par e-mail n’a pas pu être envoyée. Inutile de renvoyer le formulaire, je vous réponds sous 24 h.",
        'envoi_echoue'                    => "L'envoi a échoué. Vous pouvez m'écrire directement par e-mail ou par téléphone.",
        'recherche'                       => "Recherche des disponibilités…",
        'aucun_creneau'                   => "Plus de créneau libre ce mois-ci, essayez le mois suivant.",
        'agenda_indisponible'             => "L'agenda ne répond pas pour le moment. Réessayez plus tard ou utilisez le formulaire de demande.",
        'reservation_fermee'              => "La réservation en ligne n'est pas encore ouverte. En attendant, le formulaire de demande fonctionne.",
        'choisir_creneau'                 => "Choisissez une prestation, un jour et une heure.",
        'reservation_en_cours'            => "Réservation en cours…",
        'reservation_echouee'             => "La réservation n'a pas pu aboutir. Réessayez dans un instant ou utilisez le formulaire de demande.",
        'creneau_pris'                    => "Ce créneau vient d'être pris. Choisissez-en un autre.",
        'reservation_confirmee'           => "Votre rendez-vous « {prestation} » est confirmé pour le {date}. Une confirmation vient de partir à {email}.",
        'reservation_email_en_cours'      => "Votre rendez-vous « {prestation} » est confirmé pour le {date}. Votre confirmation par e-mail est en cours d’envoi à {email}. Si vous ne la recevez pas, contactez Elodie. Ne réservez pas une deuxième fois.",
        'reservation_sans_email'          => "Votre rendez-vous « {prestation} » est confirmé pour le {date}, mais l’envoi de la confirmation par e-mail a rencontré un problème. Contactez Elodie si vous ne la recevez pas. Ne réservez pas une deuxième fois.",
        'reservation_confirmee_telephone' => "Je vous écrirai au numéro indiqué à l'heure prévue.",
        'email_invalide'                  => "L'adresse e-mail ne semble pas valide.",
        'nom_invalide'                    => "Merci d'indiquer un nom et un prénom valides.",
        'telephone_invalide'              => "Le numéro de téléphone ne semble pas valide.",
        'champ_trop_long'                 => "Le champ « {champ} » ne doit pas dépasser {max} caractères.",
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
