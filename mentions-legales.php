<?php
require __DIR__ . '/app/bootstrap.php';

$site = config('site');
$title = "Mentions légales et confidentialité - L'éveil d'Elo";
$description = "Mentions légales et politique de confidentialité de L'éveil d'Elo.";
?>
<!DOCTYPE html>
<html lang="fr-CH">
<head>
<?php require __DIR__ . '/app/partials/head.php' ?>
<link rel="canonical" href="<?= e($site['url']) ?>mentions-legales.php">
</head>
<body>

<header class="header">
  <div class="header__inner">
    <a href="./" class="brand" aria-label="L'éveil d'Elo, retour à l'accueil">
      <span class="mark brand__mark" role="img" aria-label="Logo L'éveil d'Elo"></span>
      <span class="brand__name">L'éveil d'Elo</span>
    </a>
    <a href="./" class="legal__back">← Retour</a>
  </div>
</header>

<main class="legal">
  <div class="container legal__inner">

    <p class="eyebrow">Informations légales</p>
    <h1>Mentions légales &amp; confidentialité</h1>
    <p class="legal__update">Dernière mise à jour : 15 septembre 2026</p>

    <nav class="legal__toc" aria-label="Sommaire">
      <a href="#impressum" class="button-secondary">Mentions légales</a>
      <a href="#confidentialite" class="button-secondary">Politique de confidentialité</a>
    </nav>

    <section class="card legal__block" id="impressum">
      <h2>Mentions légales</h2>

      <h3>Éditrice du site</h3>
      <p>
        L'éveil d'Elo - Elodie Fauquex<br>
        <span class="legal__todo">Case postale …</span>
        <span class="legal__todo">NPA Localité</span>, Suisse<br>
        E-mail : <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a><br>
        Téléphone : <a href="tel:<?= e($site['phone']) ?>"><?= e($site['phone_display']) ?></a>
      </p>

      <h3>Forme juridique</h3>
      <p>
        Raison individuelle, non inscrite au registre du commerce.<br>
        Non assujettie à la TVA.
      </p>

      <h3>Hébergement</h3>
      <p>Infomaniak Network SA, Rue Eugène-Marziano 25, 1227 Les Acacias (GE), Suisse</p>

      <h3>Nature des prestations</h3>
      <p>
        Les prestations proposées (tirage de cartes, pendule, coaching spirituel, Reiki)
        relèvent du bien-être et du développement personnel. Elles ne constituent ni un
        diagnostic, ni un traitement médical, psychologique ou psychothérapeutique, et ne
        remplacent en aucun cas l'avis ou le suivi d'un professionnel de la santé.
      </p>
      <p>
        Les éclairages apportés lors des séances n'ont pas de valeur prédictive :
        chacun reste libre et responsable de ses propres décisions.
      </p>

      <h3>Propriété intellectuelle</h3>
      <p>
        L'ensemble des contenus de ce site, textes, logo, photographies et illustrations,
        est la propriété de L'éveil d'Elo, sauf mention contraire. Toute reproduction, même
        partielle, est interdite sans autorisation écrite préalable.
      </p>

      <h3>Responsabilité</h3>
      <p>
        Les informations publiées sur ce site sont données à titre indicatif et peuvent
        être modifiées à tout moment. L'éveil d'Elo ne peut être tenue responsable du
        contenu des sites externes vers lesquels renvoient certains liens, comme Instagram.
      </p>

      <h3>Droit applicable</h3>
      <p>
        Ce site est soumis au droit suisse. Les tribunaux compétents sont ceux du
        canton de Fribourg, sous réserve des fors impératifs
        prévus par la loi, notamment en faveur des consommateurs.
      </p>
    </section>

    <section class="card legal__block" id="confidentialite">
      <h2>Politique de confidentialité</h2>
      <p>
        La protection de vos données me tient à cœur. Cette page explique quelles données
        sont traitées, dans quel but et quels sont vos droits, conformément à la loi
        fédérale sur la protection des données (LPD).
      </p>

      <h3>Responsable du traitement</h3>
      <p>
        Elodie Fauquex, L'éveil d'Elo, canton de Fribourg.<br>
        Contact : <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>.
        Adresse postale : voir les <a href="#impressum">mentions légales</a>.
      </p>

      <h3>Données traitées et finalités</h3>
      <ul>
        <li>
          <strong>Formulaire de demande</strong> : nom, prénom, e-mail, téléphone,
          prestation souhaitée et message. Ces données servent uniquement à répondre à
          votre demande et à organiser un rendez-vous. Elles sont transmises par e-mail à
          Elodie, et une confirmation de réception vous est envoyée à l'adresse indiquée.
          Elles ne sont pas enregistrées sur le site lui-même.
        </li>
        <li>
          <strong>Réservation en ligne</strong> : les mêmes coordonnées, la prestation, le
          créneau choisi et votre éventuel message sont enregistrés dans l'agenda Google
          d'Elodie, qui en est aussi avertie par e-mail. Une confirmation vous est envoyée à
          l'adresse indiquée.
        </li>
        <li>
          <strong>Séances en visio</strong> : les informations de connexion vous sont
          transmises avant la séance.
        </li>
        <li>
          <strong>Bons cadeaux</strong> : les coordonnées de la personne qui offre et le
          prénom de la personne qui reçoit servent uniquement à établir et envoyer le bon.
        </li>
        <li>
          <strong>E-mail, téléphone et Instagram</strong> : les informations que vous
          transmettez servent uniquement à vous répondre.
        </li>
        <li>
          <strong>Protection contre les abus</strong> : lors de l'envoi d'un formulaire, une
          empreinte non réversible de votre adresse IP est conservée 24 heures au plus, afin
          de limiter les envois automatisés.
        </li>
        <li>
          <strong>Hébergement</strong> : comme tout hébergeur, Infomaniak enregistre des
          journaux techniques (dont l'adresse IP) nécessaires à la sécurité et au bon
          fonctionnement du site.
        </li>
      </ul>

      <h3>Confidentialité des séances</h3>
      <p>
        Ce qui est confié lors d'une séance reste strictement confidentiel et n'est jamais
        communiqué à des tiers. Aucune séance n'est enregistrée.
        Les informations touchant à la santé ou aux convictions personnelles sont traitées
        avec une discrétion particulière, uniquement dans le cadre de l'accompagnement.
      </p>

      <h3>Destinataires</h3>
      <p>
        Vos données ne sont ni vendues ni cédées. Seuls les prestataires nécessaires au
        fonctionnement du site y ont accès : Infomaniak (hébergement, en Suisse) et Google
        (Gmail pour l'envoi des e-mails, Google Agenda pour les rendez-vous et Google Fonts
        pour les polices de caractères).
      </p>

      <h3>Cookies et mesure d'audience</h3>
      <p>
        Ce site n'utilise ni cookies, ni outil de mesure d'audience. Les polices de
        caractères sont chargées depuis les serveurs de Google (Google Fonts), ce qui
        transmet votre adresse IP à Google lors de votre visite.
      </p>

      <h3>Transfert de données à l'étranger</h3>
      <p>
        Google peut traiter des données aux États-Unis. Ce transfert repose sur le
        Swiss-U.S. Data Privacy Framework, auquel Google a adhéré.
      </p>

      <h3>Durée de conservation</h3>
      <p>
        Les demandes restées sans suite sont supprimées après 12 mois. Les données liées
        aux prestations réalisées sont conservées le temps nécessaire au suivi ; les pièces
        comptables, pendant 10 ans comme l'exige la loi.
      </p>

      <h3>Vos droits</h3>
      <p>
        Vous pouvez à tout moment demander l'accès à vos données, leur rectification ou leur
        suppression, ou vous opposer à leur traitement, en écrivant à
        <a href="mailto:<?= e($site['email']) ?>"><?= e($site['email']) ?></a>.
      </p>
      <p>
        Vous pouvez également vous adresser au Préposé fédéral à la protection des données
        et à la transparence (PFPDT) :
        <a href="https://www.edoeb.admin.ch" target="_blank" rel="noopener">www.edoeb.admin.ch</a>.
      </p>

      <h3>Modifications</h3>
      <p>
        Cette politique peut être mise à jour. La date de la dernière version figure en haut
        de cette page.
      </p>
    </section>

  </div>
</main>

<footer class="footer">
  <div class="container footer__bottom">
    <p>&copy; <?= date('Y') ?> L'éveil d'Elo - Tous droits réservés</p>
    <p class="footer__legal"><a href="./">Retour à l'accueil</a></p>
  </div>
</footer>

</body>
</html>
