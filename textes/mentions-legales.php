<?php
/*
 * TEXTES DES PAGES « MENTIONS LÉGALES » ET « CONFIDENTIALITÉ ».
 *
 * Comment modifier : mêmes règles que site-text.php (texte entre guillemets "…", **gras**, *italique*).
 * - Chaque rubrique a un titre, puis ses paragraphes entre guillemets.
 * - Un retour à la ligne à l'intérieur d'un texte fait un retour à la ligne sur la page.
 * - Une liste à puces s'écrit entre crochets : ["premier point", "deuxième point"].
 * - {email}, {telephone}, {pfpdt} et {whatsapp} deviennent des liens (contacts et politiques des services).
 * - Pensez à changer la date de mise à jour après une modification.
 */

return [

    'google_titre'       => "Mentions légales et confidentialité - L'éveil d'Elo",
    'google_description' => "Mentions légales et politique de confidentialité de L'éveil d'Elo.",
    'retour'             => "← Retour",
    'surtitre'           => "Informations légales",
    'titre'              => "Mentions légales & confidentialité",
    'mise_a_jour'        => "Dernière mise à jour : 3 octobre 2026",
    'retour_accueil'     => "Retour à l'accueil",

    'mentions' => [
        'google_description' => "Mentions légales de L'éveil d'Elo : éditrice du site et informations sur les prestations.",
        'titre'     => "Mentions légales",
        'rubriques' => [
            "Éditrice du site" => [
                "L'éveil d'Elo - Elodie Fauquex
                1690 Villaz-St-Pierre
                E-mail : {email}
                Téléphone : {telephone}",
            ],
            "Forme juridique" => [
                "Raison individuelle, non inscrite au registre du commerce.
                Non assujettie à la TVA.",
            ],
            "Nature des prestations" => [
                "Les prestations proposées (tirage de cartes, pendule, coaching spirituel, Reiki) relèvent du bien-être et du développement personnel. Elles ne constituent ni un diagnostic, ni un traitement médical, psychologique ou psychothérapeutique, et ne remplacent en aucun cas l'avis ou le suivi d'un professionnel de la santé.",
                "Les éclairages apportés lors des séances n'ont pas de valeur prédictive : chacun reste libre et responsable de ses propres décisions.",
            ],
            "Propriété intellectuelle" => [
                "L'ensemble des contenus de ce site, textes, logo, photographies et illustrations, est la propriété de L'éveil d'Elo, sauf mention contraire. Toute reproduction, même partielle, est interdite sans autorisation écrite préalable.",
            ],
            "Responsabilité" => [
                "Les informations publiées sur ce site sont données à titre indicatif et peuvent être modifiées à tout moment. L'éveil d'Elo ne peut être tenue responsable du contenu des sites externes vers lesquels renvoient certains liens, comme Instagram.",
            ],
            "Droit applicable" => [
                "Ce site est soumis au droit suisse. Les tribunaux compétents sont ceux du canton de Fribourg, sous réserve des fors impératifs prévus par la loi, notamment en faveur des consommateurs.",
            ],
        ],
    ],

    'confidentialite' => [
        'google_description' => "Politique de confidentialité de L'éveil d'Elo : utilisation de vos données personnelles et droits.",
        'titre'        => "Politique de confidentialité",
        'introduction' => [
            "La protection de vos données me tient à cœur. Cette page explique quelles données sont traitées, dans quel but et quels sont vos droits, conformément à la loi fédérale sur la protection des données (LPD).",
        ],
        'rubriques' => [
            "Responsable du traitement" => [
                "Elodie Fauquex, L'éveil d'Elo, canton de Fribourg.
                Contact : {email}
                Adresse postale : voir la page des mentions légales.",
            ],
            "Données traitées et finalités" => [
                [
                    "**Formulaire de demande** : nom, prénom, e-mail, téléphone, prestation souhaitée et message. Ces données servent uniquement à répondre à votre demande et à organiser un rendez-vous. Elles sont transmises par e-mail à Elodie, et une confirmation de réception vous est envoyée à l'adresse indiquée. Elles ne sont pas enregistrées sur le site lui-même.",
                    "**Réservation en ligne** : nom, prénom, e-mail, téléphone, date de naissance (vérification de la majorité), prestation, créneau et réponses au questionnaire propre à votre prestation. Ces informations servent à organiser et préparer votre séance. Vos coordonnées, la prestation et le créneau sont enregistrés dans Google Agenda. Les réponses au questionnaire ne sont pas inscrites dans l'agenda ; elles sont transmises à Elodie par e-mail. Une confirmation avec les conditions acceptées vous est envoyée.",
                    "**Trace de votre accord** : le formulaire validé, votre identité déclarée, les accords cochés, la date et l'heure de validation et une copie des conditions et de cette politique sont conservés dans un dossier du site dont l'accès public est bloqué. Cette trace permet de retrouver votre demande et les conditions de la réservation. Elle ne constitue pas une vérification officielle d'identité ni une signature électronique qualifiée.",
                    "**Séances en visio** : les appels ont lieu sur WhatsApp avec votre numéro de téléphone, votre image et votre voix. Leur contenu est chiffré de bout en bout ; WhatsApp traite aussi des données techniques, comme votre adresse IP et la durée des appels.",
                    "**Bons cadeaux** : les coordonnées de la personne qui offre et le prénom de la personne qui reçoit servent uniquement à établir et envoyer le bon.",
                    "**E-mail, téléphone et Instagram** : les informations que vous transmettez servent uniquement à vous répondre.",
                    "**Protection contre les abus** : après l'envoi réussi d'un formulaire, une empreinte de votre adresse IP et la date et l'heure de l'envoi sont enregistrées pour limiter les envois automatisés. L'adresse IP elle-même n'est pas conservée dans ce dispositif. Les envois datant de plus de 24 heures ne sont plus pris en compte et leurs traces sont supprimées lors du prochain envoi réussi d'un formulaire ; elles peuvent donc rester enregistrées plus longtemps si aucun nouveau formulaire n'est envoyé.",
                    "**Sécurité du site** : des journaux techniques, comprenant notamment votre adresse IP, sont enregistrés pour la sécurité et le bon fonctionnement du site.",
                ],
            ],
            "Confidentialité des séances" => [
                "Ce qui est confié lors d'une séance est traité avec confidentialité, sous réserve des obligations légales applicables et du recours aux prestataires techniques décrits ci-dessous. Aucun enregistrement audio ou vidéo n'est réalisé sans un accord préalable distinct. Si vous choisissez de transmettre des informations sensibles dans le formulaire, par exemple sur votre santé ou vos convictions, votre consentement exprès est recueilli pour leur traitement dans le cadre de l'accompagnement.",
                "Les formulaires sont transmis par e-mail à Elodie. Les e-mails ordinaires ne sont pas chiffrés de bout en bout. Limitez vos réponses à ce qui est utile, ne donnez pas de détails médicaux ni d'informations identifiant des tiers ; vous pouvez indiquer que vous préférez en parler pendant la séance.",
            ],
            "Destinataires" => [
                "Vos données ne sont pas vendues. Les prestataires utilisés sont un prestataire d'hébergement en France, Google pour les e-mails et l'agenda, et WhatsApp pour les appels vidéo. Chacun traite les données liées au service concerné.",
            ],
            "Cookies et mesure d'audience" => [
                "Ce site n'utilise ni cookies, ni outil de mesure d'audience. Les polices de caractères sont hébergées directement sur ce site ; leur affichage ne nécessite aucune connexion aux serveurs de Google.",
            ],
            "Transfert de données à l'étranger" => [
                "Les données du site sont hébergées en France. Google peut traiter des données aux États-Unis. Ce transfert repose sur le Swiss-U.S. Data Privacy Framework, auquel Google a adhéré.",
                "WhatsApp est fourni depuis l'Irlande et traite des données notamment aux États-Unis, avec le Swiss-U.S. Data Privacy Framework pour les transferts couverts. Les autres pays de traitement et garanties sont décrits dans sa politique : {whatsapp}.",
            ],
            "Durée de conservation" => [
                "Les demandes restées sans suite sont supprimées au plus tard 12 mois après le dernier échange.",
                "Les questionnaires et réponses personnelles sont conservés pour préparer les séances et assurer le suivi, au maximum un an après la dernière séance de l'accompagnement concerné. Ils sont supprimés plus tôt lorsqu'ils ne sont plus utiles. Cette durée d'un an est une règle de gestion de L'éveil d'Elo, et non une obligation légale.",
                "Les preuves de réservation et d'accord (identité, prestation, tarif, accords datés et version des conditions acceptées) sont conservées pendant l'exécution de la prestation et le règlement des montants dus. Après cela, seuls les éléments nécessaires pour établir ou défendre un droit sont conservés jusqu'à l'expiration du délai de prescription applicable ou, en cas de litige en cours, jusqu'à son règlement définitif. Les réponses personnelles au questionnaire ne sont pas conservées avec ces preuves au-delà de leur durée propre, sauf si une réponse est nécessaire au traitement d'un litige précis.",
                "Les pièces comptables sont conservées pendant 10 ans à compter de la fin de l'exercice concerné. Cette obligation ne s'étend pas à l'ensemble du questionnaire.",
                "La suppression des données gérées par Elodie est effectuée manuellement par Elodie ou la personne chargée de la maintenance. Elle concerne les fichiers du site, les e-mails, les données de rendez-vous et les copies de suivi conservées sur ses appareils. Les éventuelles sauvegardes doivent également être prises en compte ; les données supprimées ne doivent pas être réintroduites lors d'une restauration.",
                "Les traces de protection contre les abus sont nettoyées selon le fonctionnement décrit plus haut. La conservation des autres journaux techniques dépend des règles du prestataire d'hébergement et des réglages d'archivage. Pour des précisions sur vos données et leur conservation, écrivez à {email}.",
            ],
            "Vos droits" => [
                "Vous pouvez à tout moment demander l'accès à vos données, leur rectification ou leur suppression, ou vous opposer à leur traitement, en écrivant à {email}. Vous pouvez retirer votre consentement pour l'avenir ; ce retrait ne remet pas en cause les traitements déjà effectués et reste soumis aux obligations légales de conservation applicables.",
                "Lorsque les conditions légales sont remplies, vous pouvez également demander la remise des données personnelles que vous avez fournies dans un format électronique couramment utilisé, ou leur transmission à un autre responsable du traitement.",
                "Les demandes d'accès sont traitées en principe gratuitement et dans un délai de 30 jours. Si ce délai ne peut pas être respecté, vous êtes informé du délai dans lequel les renseignements vous seront fournis. Les informations nécessaires pour vérifier votre identité peuvent vous être demandées afin de protéger vos données.",
                "Vous pouvez également vous adresser au Préposé fédéral à la protection des données et à la transparence (PFPDT) : {pfpdt}.",
            ],
            "Modifications" => [
                "Cette politique peut être mise à jour. La date de la dernière version figure en haut de cette page.",
            ],
        ],
    ],

];
