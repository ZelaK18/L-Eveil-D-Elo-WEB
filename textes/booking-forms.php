<?php
// Formulaires issus des documents Word. Prix, durée et format restent ceux de site-text.php.
// Modifier la version lors d'un changement de conditions ; leur empreinte est aussi vérifiée à l'envoi.
return [
    'version' => '2026-10-01',
    'intro' => 'Quelques mots pour préparer notre rencontre. Les champs marqués * sont obligatoires. Si un sujet est trop personnel, indiquez simplement que vous préférez en parler pendant la séance.',
    'privacy' => 'Elodie Fauquex utilise vos coordonnées et vos réponses pour préparer et réaliser la prestation, vous contacter et assurer la facturation. Votre formulaire et vos accords datés sont conservés dans un dossier à accès restreint sur le site et transmis à Elodie par e-mail via Google/Gmail. Seuls vos coordonnées, la prestation et le créneau sont inscrits dans Google Agenda. Google peut traiter ces données aux États-Unis. Les e-mails ordinaires ne sont pas chiffrés de bout en bout : évitez les détails médicaux et les informations identifiant des tiers ; vous pouvez réserver leur discussion à la séance. Pour vos droits et les durées de conservation, consultez la politique de confidentialité.',
    'consents' => [
        'adult' => 'Je confirme avoir au moins 18 ans, réserver pour moi-même et participer librement à cette prestation.',
        'scope' => 'J’ai compris le cadre et les limites de cette prestation. Je reste responsable de mes décisions et je m’adresse à un professionnel compétent pour toute question médicale, psychologique, juridique ou financière.',
        'terms' => 'J’ai relu et j’accepte la prestation, son format, son tarif, la date et l’heure récapitulés, ainsi que les conditions de réservation, de paiement et d’annulation ci-dessus. Je demande la réservation de ce rendez-vous.',
        'consent' => 'J’accepte expressément que mes réponses, y compris les informations sensibles que je choisis de communiquer, soient utilisées par Elodie pour cet accompagnement, conservées avec mes accords et transmises par e-mail selon les informations de confidentialité ci-dessus.',
    ],
    'services' => [
        'tirage' => [
            'title' => 'Votre tirage de cartes',
            'scope' => 'Le tirage de cartes est un support symbolique d’introspection et de réflexion, sans garantie de résultat ni certitude sur l’avenir. Il ne remplace aucun avis médical, psychologique, juridique ou financier. Ne prenez pas une décision importante uniquement sur la base du tirage. Elodie peut refuser ou reformuler une question hors cadre.',
            'fields' => [
                'domaine' => ['label' => 'Domaine principal', 'type' => 'select', 'required' => true, 'options' => ['personnel' => 'Cheminement personnel', 'relations' => 'Relations', 'professionnel' => 'Vie professionnelle', 'choix' => 'Choix ou changement', 'autre' => 'Autre']],
                'situation' => ['label' => 'Décrivez brièvement votre situation actuelle', 'required' => true],
                'question' => ['label' => 'Votre question précise pour le tirage', 'required' => true],
                'tirage' => ['label' => 'Type de tirage souhaité', 'type' => 'select', 'required' => true, 'hint' => 'Indiquez votre préférence ; Elodie adaptera le tirage au cadre de la séance réservée.', 'options' => ['une' => '1 carte · message du moment', 'trois' => '3 cartes · situation, éclairage, piste d’action', 'cinq' => '5 cartes · exploration approfondie', 'ensemble' => 'À définir avec Elodie']],
            ],
        ],
        'pendule' => [
            'title' => 'Votre séance de pendule',
            'scope' => 'Le pendule est un support symbolique d’introspection et de réflexion, sans garantie de résultat ni certitude sur l’avenir. Il ne remplace aucun avis médical, psychologique, juridique ou financier. Ne prenez pas une décision importante uniquement sur la base du pendule. Elodie peut refuser ou reformuler une question hors cadre.',
            'fields' => [
                'domaine' => ['label' => 'Domaine principal', 'type' => 'select', 'required' => true, 'options' => ['personnel' => 'Cheminement personnel', 'relations' => 'Relations', 'professionnel' => 'Vie professionnelle', 'choix' => 'Choix ou changement', 'autre' => 'Autre']],
                'situation' => ['label' => 'Décrivez brièvement votre situation actuelle', 'required' => true],
                'question' => ['label' => 'Votre question principale à clarifier', 'required' => true],
                'precisions' => ['label' => 'Options envisagées ou précisions', 'required' => false],
            ],
        ],
        'coaching' => [
            'title' => 'Votre admission en coaching',
            'scope' => 'Le coaching est un accompagnement au développement personnel et au bien-être. Il ne remplace ni un diagnostic, ni un traitement ou un suivi médical, psychologique, psychiatrique, psychothérapeutique ou paramédical. Elodie ne pose pas de diagnostic, ne garantit pas de résultat et peut interrompre ou orienter l’accompagnement si la situation dépasse son champ de compétence. Vous restez responsable de vos décisions et de vos démarches de santé.',
            'fields' => [
                'adresse' => ['label' => 'Adresse postale de facturation', 'required' => false, 'max' => 400, 'hint' => 'Rue, numéro, NPA, localité et pays, si une facture est souhaitée.'],
                'demande' => ['label' => 'Qu’est-ce qui vous amène aujourd’hui ?', 'required' => true],
                'objectif' => ['label' => 'Qu’aimeriez-vous voir évoluer grâce à cet accompagnement ?', 'required' => true],
                'essais' => ['label' => 'Qu’avez-vous déjà essayé et qu’est-ce qui vous a aidé ?', 'required' => true, 'hint' => 'Vous pouvez aussi indiquer qu’il s’agit de votre première démarche.'],
                'ressources' => ['label' => 'Quelles sont vos principales ressources ou forces aujourd’hui ?', 'required' => true, 'hint' => 'Si vous ne savez pas encore, nous pourrons les identifier ensemble.'],
            ],
        ],
    ],
];
