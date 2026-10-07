<?php
// Formulaires issus des documents Word. Prix, durée et format restent ceux de site-text.php.
// Modifier la version lors d'un changement de conditions ; leur empreinte est aussi vérifiée à l'envoi.
return [
    'version' => '2026-10-07.1',
    'intro' => 'Quelques mots suffisent, gardez les détails personnels pour la séance. Les champs avec * sont obligatoires.',
    'confirmation_notice' => 'Vous pouvez encore corriger vos informations et votre créneau avant de confirmer. En cliquant sur « {bouton} », vous envoyez votre réservation au tarif affiché. Le contrat est conclu lorsque le site confirme votre réservation, après vérification de la disponibilité du créneau. Une confirmation vous est envoyée par e-mail. Aucun paiement n’est prélevé en ligne.',
    'terms_summary' => 'Les séances payantes se règlent par TWINT avant la séance, ou avant la première séance pour les packs et programmes. La séance découverte offerte reste gratuite. Prévenez au moins {delai} avant pour annuler ou reporter. Passé ce délai, une séance payante peut être due selon les conditions.',
    'privacy' => 'Elodie Fauquex utilise vos coordonnées et vos réponses pour préparer et réaliser la prestation, vous contacter et assurer la facturation. Votre formulaire et vos accords datés sont conservés dans un dossier à accès restreint sur le site et transmis à Elodie par e-mail via Google/Gmail. Seuls vos coordonnées, la prestation et le créneau sont inscrits dans Google Agenda. Google peut traiter ces données aux États-Unis. Les e-mails ordinaires ne sont pas chiffrés de bout en bout. Évitez les détails médicaux et les informations identifiant des tiers, vous pouvez réserver leur discussion à la séance. Pour vos droits et les durées de conservation, consultez la politique de confidentialité.',
    'consents' => [
        'terms' => 'J’ai 18 ans ou plus et je réserve librement pour moi-même. J’ai lu et j’accepte la prestation, le créneau et le tarif choisis, ainsi que les conditions de séance, de paiement et d’annulation.',
        'consent' => 'J’accepte expressément l’utilisation de mes réponses, y compris sensibles, pour cet accompagnement, leur conservation avec mes accords et leur transmission à Elodie par e-mail, selon la politique de confidentialité.',
    ],
    'services' => [
        'tirage' => [
            'title' => 'Votre tirage de cartes',
            'summary' => 'Les cartes offrent un éclairage personnel, sans certitude sur l’avenir ni remplacement d’un avis professionnel.',
            'scope' => 'Le tirage de cartes est un support symbolique d’introspection et de réflexion, sans garantie de résultat ni certitude sur l’avenir. Il ne remplace aucun avis médical, psychologique, juridique ou financier. Ne prenez pas une décision importante uniquement sur la base du tirage. Elodie peut refuser ou reformuler une question hors cadre.',
            'fields' => [
                'domaine' => ['label' => 'Domaine principal', 'type' => 'select', 'required' => true, 'options' => ['personnel' => 'Cheminement personnel', 'relations' => 'Relations', 'professionnel' => 'Vie professionnelle', 'choix' => 'Choix ou changement', 'autre' => 'Autre']],
                'situation' => ['label' => 'Votre situation en quelques mots', 'required' => true],
                'question' => ['label' => 'Votre question', 'required' => true],
                'tirage' => ['label' => 'Tirage souhaité', 'type' => 'select', 'required' => true, 'hint' => 'Elodie adaptera le tirage à votre question.', 'options' => ['une' => '1 carte, message du moment', 'trois' => '3 cartes, situation, éclairage, piste d’action', 'cinq' => '5 cartes, exploration approfondie', 'ensemble' => 'À définir avec Elodie']],
            ],
        ],
        'pendule' => [
            'title' => 'Votre séance de pendule',
            'summary' => 'Le pendule offre un éclairage personnel, sans certitude sur l’avenir ni remplacement d’un avis professionnel.',
            'scope' => 'Le pendule est un support symbolique d’introspection et de réflexion, sans garantie de résultat ni certitude sur l’avenir. Il ne remplace aucun avis médical, psychologique, juridique ou financier. Ne prenez pas une décision importante uniquement sur la base du pendule. Elodie peut refuser ou reformuler une question hors cadre.',
            'fields' => [
                'domaine' => ['label' => 'Domaine principal', 'type' => 'select', 'required' => true, 'options' => ['personnel' => 'Cheminement personnel', 'relations' => 'Relations', 'professionnel' => 'Vie professionnelle', 'choix' => 'Choix ou changement', 'autre' => 'Autre']],
                'situation' => ['label' => 'Votre situation en quelques mots', 'required' => true],
                'question' => ['label' => 'Votre question', 'required' => true],
                'precisions' => ['label' => 'Précisions utiles', 'required' => false],
            ],
        ],
        'coaching' => [
            'title' => 'Votre coaching',
            'summary' => 'Un accompagnement personnel, sans garantie de résultat ni remplacement d’un suivi médical ou psychologique.',
            'scope' => 'Le coaching est un accompagnement au développement personnel et au bien-être. Il ne remplace ni un diagnostic, ni un traitement ou un suivi médical, psychologique, psychiatrique, psychothérapeutique ou paramédical. Elodie ne pose pas de diagnostic, ne garantit pas de résultat et peut interrompre ou orienter l’accompagnement si la situation dépasse son champ de compétence. Vous restez responsable de vos décisions et de vos démarches de santé.',
            'fields' => [
                'demande' => ['label' => 'Qu’est-ce qui vous amène aujourd’hui ?', 'required' => true],
                'objectif' => ['label' => 'Qu’aimeriez-vous faire évoluer ?', 'required' => true],
                'essais' => ['label' => 'Ce que vous avez déjà essayé', 'required' => true, 'hint' => 'Si c’est votre première démarche, indiquez-le simplement.'],
                'ressources' => ['label' => 'Ce qui vous aide au quotidien', 'required' => true, 'hint' => 'Vous pouvez préciser que vous souhaitez en parler ensemble.'],
            ],
        ],
    ],
];
