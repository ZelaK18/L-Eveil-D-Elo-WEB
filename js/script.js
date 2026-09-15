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

  const syncFormat = () => {
    const formats = new Set([...prestations]
      .filter(box => box.checked && box.dataset.format)
      .map(box => box.dataset.format));
    formatField.value = [...formats].join(" et ") || formatField.defaultValue;
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

  form.addEventListener("submit", async e => {
    e.preventDefault();
    if (!form.reportValidity()) {
      setStatus("Merci de compléter les champs obligatoires.", "is-error");
      return;
    }

    const data = new FormData(form);
    const val = name => data.getAll(name).join(", ").trim();
    const subject = `Demande de rendez-vous : ${val("nom")} ${val("prenom")}`;
    const destination = form.getAttribute("action");

    if (destination.startsWith("mailto:")) {
      const body = [
        `Nom : ${val("nom")}`,
        `Prénom : ${val("prenom")}`,
        `E-mail : ${val("email")}`,
        `Téléphone : ${val("telephone")}`,
        `Prestation(s) : ${val("prestation") || "Non précisé"}`,
        `Format : ${val("format")}`,
        "",
        "Message :",
        val("message") || "Aucun message"
      ].join("\n");
      location.href = `${destination}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      setStatus("Votre logiciel de messagerie s'ouvre avec le message pré-rempli, il ne reste qu'à l'envoyer.", "is-ok");
      return;
    }

    const submit = form.querySelector(".form__submit");
    submit.disabled = true;
    setStatus("Envoi en cours…");
    data.append("subject", subject);
    try {
      const response = await fetch(destination, { method: "POST", body: data, headers: { Accept: "application/json" } });
      if (!response.ok) throw new Error(response.status);
      form.reset();
      setStatus("Merci ! Votre demande est bien partie, je vous réponds sous 48 h.", "is-ok");
    } catch {
      setStatus("L'envoi a échoué. Vous pouvez m'écrire directement par e-mail ou par téléphone.", "is-error");
    } finally {
      submit.disabled = false;
    }
  });
});
