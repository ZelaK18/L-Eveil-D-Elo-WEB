/* =========================================================
   L'ÉVEIL D'ELO — Scripts
   ========================================================= */

/* ─────────────────────────────────────────────────────────
   ⚙️  CONFIGURATION — c'est ici (et seulement ici) qu'on modifie
   ───────────────────────────────────────────────────────── */
const CONFIG = {

  // 1) E-mail qui reçoit les demandes du formulaire
  email: "contact@leveildelo.ch",

  // 2) GOOGLE AGENDA — page de rendez-vous.
  //    Coller ici le lien obtenu dans Google Agenda (bouton « Partager »
  //    de la page de rendez-vous). Il ressemble à :
  //      https://calendar.app.google/XXXXXXXXXXXX
  //    Laisser "" tant qu'il n'existe pas : le bouton bascule alors
  //    automatiquement sur le formulaire ci-contre.
  bookingUrl: "",

  //    Optionnel — une page de rendez-vous par prestation.
  //    ⚠️ Un compte Google gratuit n'autorise QU'UNE seule page de
  //    rendez-vous : laisser vide et utiliser bookingUrl ci-dessus.
  //    Avec un abonnement Google Workspace (pages illimitées), remplir
  //    ces lignes : chaque bouton « Réserver » mènera directement à la
  //    bonne page, avec la bonne durée.
  bookingUrls: {
    "Tirage de cartes":   "",
    "Pendule":            "",
    "Coaching spirituel": ""
  },

  // 3) Envoi du formulaire.
  //    - "" (vide)  → ouvre le logiciel de messagerie avec le message pré-rempli.
  //                   Fonctionne immédiatement, sans inscription.
  //    - une URL    → envoi direct par e-mail sans quitter le site.
  //      Exemples : "https://formspree.io/f/xxxxxxx"
  //                 "https://api.web3forms.com/submit"   (+ accessKey ci-dessous)
  formEndpoint: "",

  // Uniquement si vous utilisez Web3Forms
  web3formsKey: "",

  // 4) Format de séance imposé par chaque prestation.
  //    Le champ « Format » du formulaire se remplit tout seul à partir d'ici :
  //    il suffit de modifier cette table pour changer le comportement.
  formats: {
    "Tirage de cartes":   "Par téléphone",
    "Pendule":            "Par téléphone",
    "Coaching spirituel": "En visio",
    "Bon cadeau":         "Format papier ou PDF"
  },
  formatParDefaut: "Selon la prestation choisie"
};
/* ───────────────────────────────────────────────────────── */


document.addEventListener("DOMContentLoaded", () => {

  /* ---------- Année dans le pied de page ---------- */
  const year = document.getElementById("year");
  if (year) year.textContent = new Date().getFullYear();


  /* ---------- Menu burger ---------- */
  const burger = document.getElementById("burger");
  const nav    = document.getElementById("nav");

  const closeMenu = () => {
    nav.classList.remove("is-open");
    burger.classList.remove("is-open");
    burger.setAttribute("aria-expanded", "false");
    burger.setAttribute("aria-label", "Ouvrir le menu");
  };

  burger.addEventListener("click", () => {
    const open = nav.classList.toggle("is-open");
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", String(open));
    burger.setAttribute("aria-label", open ? "Fermer le menu" : "Ouvrir le menu");
  });

  nav.querySelectorAll("a").forEach(link => link.addEventListener("click", closeMenu));

  document.addEventListener("keydown", e => {
    if (e.key === "Escape" && nav.classList.contains("is-open")) {
      closeMenu();
      burger.focus();
    }
  });


  /* ---------- En-tête : ombre au défilement + bouton « remonter » ---------- */
  const header = document.getElementById("header");
  const toTop  = document.getElementById("toTop");

  const onScroll = () => {
    const y = window.scrollY;
    header.classList.toggle("is-scrolled", y > 20);
    toTop.classList.toggle("is-visible", y > 600);
  };
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });


  /* ---------- Lien actif selon la section visible ---------- */
  const sections = [...document.querySelectorAll("main section[id]")];
  const navLinks = [...document.querySelectorAll(".nav__link")];

  if ("IntersectionObserver" in window && sections.length) {
    const spy = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const id = entry.target.id;
        navLinks.forEach(link =>
          link.classList.toggle("is-active", link.getAttribute("href") === "#" + id)
        );
      });
    }, { rootMargin: "-45% 0px -50% 0px", threshold: 0 });

    sections.forEach(section => spy.observe(section));
  }


  /* ---------- Apparition des blocs au défilement ---------- */
  const revealables = document.querySelectorAll(".reveal");

  if ("IntersectionObserver" in window) {
    const io = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-in");
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: "0px 0px -8% 0px", threshold: .12 });

    revealables.forEach(el => io.observe(el));
  } else {
    revealables.forEach(el => el.classList.add("is-in"));
  }


  /* ---------- Photo d'accueil : repère visuel si elle manque ---------- */
  const portraitImg = document.querySelector("[data-fallback]");
  if (portraitImg) {
    const markEmpty = () => portraitImg.closest(".portrait").classList.add("is-empty");
    portraitImg.addEventListener("error", markEmpty);
    if (portraitImg.complete && portraitImg.naturalWidth === 0) markEmpty();
  }


  /* ---------- Réservation Google Agenda ----------
     Sans page de rendez-vous configurée, il n'y a rien à faire : tous ces
     boutons pointent déjà sur #rendez-vous et le navigateur y amène tout
     seul. On ne réécrit leur destination que si un lien existe. */
  const versGoogleAgenda = (lien, url) => {
    if (!lien || !url) return;
    lien.href = url;
    lien.target = "_blank";
    lien.rel = "noopener";
  };

  // Bouton principal « Voir les disponibilités »
  if (CONFIG.bookingUrl) {
    versGoogleAgenda(document.getElementById("bookingLink"), CONFIG.bookingUrl);
    document.getElementById("bookingHint")?.remove();
  }

  // Boutons « Réserver » des cartes : page dédiée si elle existe, sinon la générale
  document.querySelectorAll(".presta__link[data-prestation]").forEach(lien =>
    versGoogleAgenda(lien, CONFIG.bookingUrls?.[lien.dataset.prestation] || CONFIG.bookingUrl)
  );


  /* ---------- Format de séance déduit de la prestation cochée ---------- */
  const formatField = document.getElementById("formatAuto");
  const prestationBoxes = document.querySelectorAll("input[name='prestation']");

  const syncFormat = () => {
    if (!formatField) return;
    // formats distincts des prestations cochées, sans doublon et dans l'ordre
    const formats = [...prestationBoxes]
      .filter(box => box.checked)
      .map(box => CONFIG.formats[box.value])
      .filter((f, i, all) => f && all.indexOf(f) === i);

    formatField.value = formats.length ? formats.join(" et ") : CONFIG.formatParDefaut;
  };

  prestationBoxes.forEach(box => box.addEventListener("change", syncFormat));
  syncFormat();


  /* ---------- Raccourci « Commander un bon cadeau » ---------- */
  document.querySelectorAll("[data-prefill='bon-cadeau']").forEach(btn => {
    btn.addEventListener("click", () => {
      const checkbox = document.getElementById("chk-bon-cadeau");
      if (checkbox) checkbox.checked = true;
      syncFormat();
    });
  });


  /* ---------- Formulaire de demande ---------- */
  const form   = document.getElementById("rdvForm");
  const status = document.getElementById("formStatus");

  const setStatus = (text, state = "") => {
    status.textContent = text;
    status.className = "form__status" + (state ? " " + state : "");
  };

  const collect = () => {
    const data = new FormData(form);
    const prestations = data.getAll("prestation");
    return {
      prenom:      (data.get("prenom")    || "").trim(),
      nom:         (data.get("nom")       || "").trim(),
      email:       (data.get("email")     || "").trim(),
      telephone:   (data.get("telephone") || "").trim(),
      format:       data.get("format")    || "",
      message:     (data.get("message")   || "").trim(),
      prestations:  prestations.length ? prestations.join(", ") : "Non précisé"
    };
  };

  const buildBody = v => [
    `Nom : ${v.nom}`,
    `Prénom : ${v.prenom}`,
    `E-mail : ${v.email}`,
    `Téléphone : ${v.telephone}`,
    `Prestation(s) : ${v.prestations}`,
    `Format : ${v.format}`,
    "",
    "Message :",
    v.message || "—"
  ].join("\n");

  form?.addEventListener("submit", async e => {
    e.preventDefault();

    if (!form.reportValidity()) {
      setStatus("Merci de compléter les champs obligatoires.", "is-error");
      return;
    }

    const values  = collect();
    const subject = `Demande de rendez-vous — ${values.nom} ${values.prenom}`.trim();
    const submit  = form.querySelector(".form__submit");

    // Cas A : un service d'envoi est configuré
    if (CONFIG.formEndpoint) {
      submit.disabled = true;
      setStatus("Envoi en cours…");

      try {
        const payload = { ...values, _subject: subject };
        if (CONFIG.web3formsKey) payload.access_key = CONFIG.web3formsKey;

        const response = await fetch(CONFIG.formEndpoint, {
          method: "POST",
          headers: { "Content-Type": "application/json", Accept: "application/json" },
          body: JSON.stringify(payload)
        });

        if (!response.ok) throw new Error("Réponse " + response.status);

        form.reset();
        setStatus("Merci ! Votre demande est bien partie, je vous réponds sous 48 h.", "is-ok");
      } catch (error) {
        setStatus(
          `L'envoi a échoué. Vous pouvez m'écrire directement à ${CONFIG.email}.`,
          "is-error"
        );
      } finally {
        submit.disabled = false;
      }
      return;
    }

    // Cas B : pas de service → on ouvre le logiciel de messagerie, pré-rempli
    const mailto =
      `mailto:${CONFIG.email}` +
      `?subject=${encodeURIComponent(subject)}` +
      `&body=${encodeURIComponent(buildBody(values))}`;

    window.location.href = mailto;
    setStatus(
      "Votre logiciel de messagerie s'ouvre avec le message pré-rempli — il ne reste qu'à l'envoyer.",
      "is-ok"
    );
  });

});
