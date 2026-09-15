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
    'mise_a_jour'        => "Dernière mise à jour : 15 septembre 2026",
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
                    "**Réservation en ligne** : les mêmes coordonnées, la prestation, le créneau choisi et votre éventuel message sont enregistrés dans l'agenda Google d'Elodie, qui en est aussi avertie par e-mail. Une confirmation vous est envoyée à l'adresse indiquée.",
                    "**Séances en visio** : les informations de connexion vous sont transmises avant la séance.",
                    "**Bons cadeaux** : les coordonnées de la personne qui offre et le prénom de la personne qui reçoit servent uniquement à établir et envoyer le bon.",
                    "**E-mail, téléphone et Instagram** : les informations que vous transmettez servent uniquement à vous répondre.",
                    "**Protection contre les abus** : lors de l'envoi d'un formulaire, une empreinte non réversible de votre adresse IP est conservée 24 heures au plus, afin de limiter les envois automatisés.",
                    "**Hébergement** : comme tout hébergeur, Infomaniak enregistre des journaux techniques (dont l'adresse IP) nécessaires à la sécurité et au bon fonctionnement du site.",
                ],
            ],
            "Confidentialité des séances" => [
                "Ce qui est confié lors d'une séance reste strictement confidentiel et n'est jamais communiqué à des tiers. Aucune séance n'est enregistrée. Les informations touchant à la santé ou aux convictions personnelles sont traitées avec une discrétion particulière, uniquement dans le cadre de l'accompagnement.",
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
                "Les demandes restées sans suite sont supprimées après 12 mois. Les données liées aux prestations réalisées sont conservées le temps nécessaire au suivi ; les pièces comptables, pendant 10 ans comme l'exige la loi.",
            ],
            "Vos droits" => [
                "Vous pouvez à tout moment demander l'accès à vos données, leur rectification ou leur suppression, ou vous opposer à leur traitement, en écrivant à {email}.",
                "Vous pouvez également vous adresser au Préposé fédéral à la protection des données et à la transparence (PFPDT) : {pfpdt}.",
            ],
            "Modifications" => [
                "Cette politique peut être mise à jour. La date de la dernière version figure en haut de cette page.",
            ],
        ],
    ],

];
