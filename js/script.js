const CONFIG = {
  // Adresse qui reçoit les demandes du formulaire
  email: "contact@leveildelo.ch",

  // Lien de la page de rendez-vous Google Agenda (vide : les boutons mènent au formulaire)
  bookingUrl: "",

  // Envoi direct du formulaire, par exemple Formspree ou Web3Forms (vide : ouvre la messagerie)
  formEndpoint: "",
  web3formsKey: "",

  // Format de séance affiché selon la prestation cochée
  formats: {
    "Tirage de cartes": "Par téléphone",
    "Pendule": "Par téléphone",
    "Coaching spirituel": "En visio",
    "Bon cadeau": "Format papier ou PDF"
  },
  formatParDefaut: "Selon la prestation choisie"
};

document.addEventListener("DOMContentLoaded", () => {
  const $ = id => document.getElementById(id);
  const header = $("header");
  const nav = $("nav");
  const burger = $("burger");
  const toTop = $("toTop");
  const form = $("rdvForm");
  const status = $("formStatus");
  const formatField = $("formatAuto");
  const prestations = form.querySelectorAll("input[name='prestation']");

  $("year").textContent = new Date().getFullYear();

  const setMenu = open => {
    nav.classList.toggle("is-open", open);
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", open);
    burger.setAttribute("aria-label", open ? "Fermer le menu" : "Ouvrir le menu");
  };
  burger.addEventListener("click", () => setMenu(!nav.classList.contains("is-open")));
  nav.querySelectorAll("a").forEach(link => link.addEventListener("click", () => setMenu(false)));
  document.addEventListener("keydown", e => {
    if (e.key === "Escape" && nav.classList.contains("is-open")) {
      setMenu(false);
      burger.focus();
    }
  });

  const onScroll = () => {
    header.classList.toggle("is-scrolled", scrollY > 20);
    toTop.classList.toggle("is-visible", scrollY > 600);
  };
  onScroll();
  addEventListener("scroll", onScroll, { passive: true });

  const observe = (targets, options, onEnter) => {
    const io = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) onEnter(entry.target, io);
    }), options);
    targets.forEach(target => io.observe(target));
  };

  const navLinks = nav.querySelectorAll(".nav__link");
  observe(document.querySelectorAll("main section[id]"), { rootMargin: "-45% 0px -50% 0px" }, section =>
    navLinks.forEach(link => link.classList.toggle("is-active", link.hash === "#" + section.id))
  );
  observe(document.querySelectorAll(".reveal"), { rootMargin: "0px 0px -8% 0px", threshold: .12 }, (el, io) => {
    el.classList.add("is-in");
    io.unobserve(el);
  });

  const photo = document.querySelector(".portrait__img");
  const sansPhoto = () => photo.closest(".portrait").classList.add("is-empty");
  photo.addEventListener("error", sansPhoto);
  if (photo.complete && !photo.naturalWidth) sansPhoto();

  if (CONFIG.bookingUrl) {
    $("bookingHint").remove();
    document.querySelectorAll("#bookingLink, .presta__link").forEach(lien =>
      Object.assign(lien, { href: CONFIG.bookingUrl, target: "_blank", rel: "noopener" })
    );
  }

  const syncFormat = () => {
    const formats = new Set([...prestations]
      .filter(box => box.checked)
      .map(box => CONFIG.formats[box.value])
      .filter(Boolean));
    formatField.value = [...formats].join(" et ") || CONFIG.formatParDefaut;
  };
  prestations.forEach(box => box.addEventListener("change", syncFormat));
  syncFormat();

  document.querySelector("[data-prefill='bon-cadeau']").addEventListener("click", () => {
    $("chk-bon-cadeau").checked = true;
    syncFormat();
  });

  const setStatus = (text, state = "") => {
    status.textContent = text;
    status.className = ("form__status " + state).trim();
  };

  const collect = () => {
    const data = new FormData(form);
    const val = name => String(data.get(name) || "").trim();
    const choix = data.getAll("prestation");
    return {
      nom: val("nom"),
      prenom: val("prenom"),
      email: val("email"),
      telephone: val("telephone"),
      format: val("format"),
      message: val("message"),
      prestations: choix.length ? choix.join(", ") : "Non précisé"
    };
  };

  form.addEventListener("submit", async e => {
    e.preventDefault();
    if (!form.reportValidity()) {
      setStatus("Merci de compléter les champs obligatoires.", "is-error");
      return;
    }

    const v = collect();
    const subject = `Demande de rendez-vous : ${v.nom} ${v.prenom}`;

    if (!CONFIG.formEndpoint) {
      const body = [
        `Nom : ${v.nom}`,
        `Prénom : ${v.prenom}`,
        `E-mail : ${v.email}`,
        `Téléphone : ${v.telephone}`,
        `Prestation(s) : ${v.prestations}`,
        `Format : ${v.format}`,
        "",
        "Message :",
        v.message || "Aucun message"
      ].join("\n");
      location.href = `mailto:${CONFIG.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      setStatus("Votre logiciel de messagerie s'ouvre avec le message pré-rempli, il ne reste qu'à l'envoyer.", "is-ok");
      return;
    }

    const submit = form.querySelector(".form__submit");
    submit.disabled = true;
    setStatus("Envoi en cours…");
    try {
      const response = await fetch(CONFIG.formEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ ...v, _subject: subject, access_key: CONFIG.web3formsKey || undefined })
      });
      if (!response.ok) throw new Error(response.status);
      form.reset();
      setStatus("Merci ! Votre demande est bien partie, je vous réponds sous 48 h.", "is-ok");
    } catch {
      setStatus(`L'envoi a échoué. Vous pouvez m'écrire directement à ${CONFIG.email}.`, "is-error");
    } finally {
      submit.disabled = false;
    }
  });
});
