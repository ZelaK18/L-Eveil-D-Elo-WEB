<?php
/*
 * TEXTES DE LA PAGE « MENTIONS LÉGALES ET CONFIDENTIALITÉ ».
 *
 * Comment modifier : mêmes règles que site-text.php (texte entre guillemets "…", **gras**, *italique*).
 * - Chaque rubrique a un titre, puis ses paragraphes entre guillemets.
 * - Un retour à la ligne à l'intérieur d'un texte fait un retour à la ligne sur la page.
 * - Une liste à puces s'écrit entre crochets : ["premier point", "deuxième point"].
 * - {email}, {telephone} et {pfpdt} deviennent des liens (e-mail et téléphone de app/config.php, site du PFPDT).
 * - Pensez à changer la date de mise à jour après une modification.
 */

return [

    'google_titre'       => "Mentions légales et confidentialité - L'éveil d'Elo",
    'google_description' => "Mentions légales et politique de confidentialité de L'éveil d'Elo.",
    'retour'             => "← Retour",
    'surtitre'           => "Informations légales",
    'titre'              => "Mentions légales & confidentialité",
    'mise_a_jour'        => "Dernière mise à jour : 1er octobre 2026",
    'retour_accueil'     => "Retour à l'accueil",

    'mentions' => [
        'titre'     => "Mentions légales",
        'rubriques' => [
            "Éditrice du site" => [
                "L'éveil d'Elo - Elodie Fauquex
                Case postale …, NPA Localité, Suisse
                E-mail : {email}
                Téléphone : {telephone}",
            ],
            "Forme juridique" => [
                "Raison individuelle, non inscrite au registre du commerce.
                Non assujettie à la TVA.",
            ],
            "Hébergement" => [
                "Infomaniak Network SA, Rue Eugène-Marziano 25, 1227 Les Acacias (GE), Suisse",
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
        'titre'        => "Politique de confidentialité",
        'introduction' => [
            "La protection de vos données me tient à cœur. Cette page explique quelles données sont traitées, dans quel but et quels sont vos droits, conformément à la loi fédérale sur la protection des données (LPD).",
        ],
        'rubriques' => [
            "Responsable du traitement" => [
                "Elodie Fauquex, L'éveil d'Elo, canton de Fribourg.
                Contact : {email}
                Adresse postale : voir les mentions légales ci-dessus.",
            ],
            "Données traitées et finalités" => [
                [
                    "**Formulaire de demande** : nom, prénom, e-mail, téléphone, prestation souhaitée et message. Ces données servent uniquement à répondre à votre demande et à organiser un rendez-vous. Elles sont transmises par e-mail à Elodie, et une confirmation de réception vous est envoyée à l'adresse indiquée. Elles ne sont pas enregistrées sur le site lui-même.",
                    "**Réservation en ligne** : nom, prénom, e-mail, téléphone, date de naissance (vérification de la majorité), prestation, créneau et réponses au questionnaire propre à votre prestation. L'adresse de facturation est facultative pour le coaching. Ces informations servent à organiser et préparer votre séance. Vos coordonnées, la prestation et le créneau sont enregistrés dans Google Agenda. Les réponses au questionnaire ne sont pas inscrites dans l'agenda ; elles sont transmises à Elodie par e-mail. Une confirmation avec les conditions acceptées vous est envoyée.",
                    "**Trace de votre accord** : le formulaire validé, votre identité déclarée, les accords cochés, la date et l'heure de validation et une copie des conditions et de cette politique sont conservés dans un dossier du site dont l'accès public est bloqué. Cette trace permet de retrouver votre demande et les conditions de la réservation. Elle ne constitue pas une vérification officielle d'identité ni une signature électronique qualifiée.",
                    "**Séances en visio** : les informations de connexion vous sont transmises avant la séance.",
                    "**Bons cadeaux** : les coordonnées de la personne qui offre et le prénom de la personne qui reçoit servent uniquement à établir et envoyer le bon.",
                    "**E-mail, téléphone et Instagram** : les informations que vous transmettez servent uniquement à vous répondre.",
                    "**Protection contre les abus** : lors de l'envoi d'un formulaire, une empreinte non réversible de votre adresse IP est conservée 24 heures au plus, afin de limiter les envois automatisés.",
                    "**Hébergement** : comme tout hébergeur, Infomaniak enregistre des journaux techniques (dont l'adresse IP) nécessaires à la sécurité et au bon fonctionnement du site.",
                ],
            ],
            "Confidentialité des séances" => [
                "Ce qui est confié lors d'une séance est traité avec confidentialité, sous réserve des obligations légales applicables et du recours aux prestataires techniques décrits ci-dessous. Aucun enregistrement audio ou vidéo n'est réalisé sans un accord préalable distinct. Si vous choisissez de transmettre des informations sensibles dans le formulaire, par exemple sur votre santé ou vos convictions, votre consentement exprès est recueilli pour leur traitement dans le cadre de l'accompagnement.",
                "Les formulaires sont transmis par e-mail à Elodie. Les e-mails ordinaires ne sont pas chiffrés de bout en bout. Limitez vos réponses à ce qui est utile, ne donnez pas de détails médicaux ni d'informations identifiant des tiers ; vous pouvez indiquer que vous préférez en parler pendant la séance.",
            ],
            "Destinataires" => [
                "Vos données ne sont ni vendues ni cédées. Seuls les prestataires nécessaires au fonctionnement du site y ont accès : Infomaniak (hébergement, en Suisse) et Google (Gmail pour l'envoi des e-mails, Google Agenda pour les rendez-vous et Google Fonts pour les polices de caractères).",
            ],
            "Cookies et mesure d'audience" => [
                "Ce site n'utilise ni cookies, ni outil de mesure d'audience. Les polices de caractères sont chargées depuis les serveurs de Google (Google Fonts), ce qui transmet votre adresse IP à Google lors de votre visite.",
            ],
            "Transfert de données à l'étranger" => [
                "Google peut traiter des données aux États-Unis. Ce transfert repose sur le Swiss-U.S. Data Privacy Framework, auquel Google a adhéré.",
            ],
            "Durée de conservation" => [
                "Les demandes restées sans suite sont supprimées après 12 mois. Les réponses personnelles liées aux prestations sont conservées le temps nécessaire au suivi, puis supprimées lorsqu'elles ne sont plus nécessaires. Les traces d'accord sont conservées dans la mesure nécessaire à la gestion de la relation contractuelle et à la défense de droits ; elles ne justifient pas de conserver indéfiniment les réponses personnelles. Les pièces comptables sont conservées pendant 10 ans. Ces durées concernent aussi les copies présentes dans les e-mails et les dossiers du site ; leur suppression est effectuée par Elodie ou la personne chargée de la maintenance.",
            ],
            "Vos droits" => [
                "Vous pouvez à tout moment demander l'accès à vos données, leur rectification ou leur suppression, ou vous opposer à leur traitement, en écrivant à {email}. Vous pouvez retirer votre consentement pour l'avenir ; ce retrait ne remet pas en cause les traitements déjà effectués et reste soumis aux obligations légales de conservation applicables.",
                "Vous pouvez également vous adresser au Préposé fédéral à la protection des données et à la transparence (PFPDT) : {pfpdt}.",
            ],
            "Modifications" => [
                "Cette politique peut être mise à jour. La date de la dernière version figure en haut de cette page.",
            ],
        ],
    ],

];
