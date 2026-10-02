document.addEventListener("DOMContentLoaded", () => {
  document.documentElement.classList.add("js");
  const $ = id => document.getElementById(id);
  const messages = JSON.parse($("messages").textContent);
  const header = $("header");
  const nav = $("nav");
  const burger = $("burger");
  const toTop = $("toTop");
  const form = $("rdvForm");
  const status = $("formStatus");

  const setMenu = open => {
    nav.classList.toggle("is-open", open);
    burger.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", open);
    burger.setAttribute("aria-label", open ? "Fermer le menu" : "Ouvrir le menu");
  };
  burger.addEventListener("click", () => setMenu(!nav.classList.contains("is-open")));
  header.querySelectorAll("a").forEach(link => link.addEventListener("click", () => setMenu(false)));
  ["pointerdown", "focusin"].forEach(event => document.addEventListener(event, e => {
    if (!header.contains(e.target)) setMenu(false);
  }));
  const compactMenu = matchMedia("(max-width: 1100px)");
  compactMenu.addEventListener("change", () => {
    setMenu(false);
    if (compactMenu.matches && nav.contains(document.activeElement)) burger.focus();
  });
  // Le décalage suit aussi les retours à la ligne lorsque le texte est agrandi.
  new ResizeObserver(() => {
    document.documentElement.style.setProperty("--header-offset", `${header.offsetHeight}px`);
  }).observe(header);
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
  observe(document.querySelectorAll(".reveal"), { rootMargin: "0px 0px -8% 0px" }, (el, io) => {
    el.classList.add("is-in");
    io.unobserve(el);
  });

  // Prestations : le nom d'une formule ouvre son détail par-dessus la carte, la croix (ou Échap) referme.
  const offers = document.querySelectorAll(".tag--offre");
  const setOffer = (button, open) => {
    const panel = $(button.getAttribute("aria-controls"));
    button.setAttribute("aria-expanded", open);
    panel.hidden = !open;
    // Le clavier ne doit pas atteindre les liens masqués par le détail de la formule.
    [...panel.parentElement.children].filter(el => !el.classList.contains("presta__offre"))
      .forEach(el => { el.inert = open; });
  };
  // preventScroll : le focus seul ferait remonter la page trop haut.
  const closeOffer = (button, focus = true) => {
    setOffer(button, false);
    if (focus) button.focus({ preventScroll: true });
  };
  // Remonte juste assez pour voir la croix sous l'en-tête. On mesure la carte : le détail, lui, glisse en apparaissant.
  const showClose = card => {
    const hidden = header.offsetHeight + 16 - card.getBoundingClientRect().top;
    if (hidden > 0) scrollBy({ top: -hidden });
  };
  offers.forEach(button => {
    const panel = $(button.getAttribute("aria-controls"));
    const close = panel.querySelector("[data-close]");
    button.addEventListener("click", () => {
      offers.forEach(other => {
        if (other !== button && other.getAttribute("aria-expanded") === "true") closeOffer(other, false);
      });
      setOffer(button, true);
      close.focus({ preventScroll: true });
      showClose(button.closest(".presta"));
    });
    close.addEventListener("click", () => closeOffer(button));
    panel.querySelector("[data-service]").addEventListener("click", () => closeOffer(button, false));
    panel.addEventListener("keydown", e => {
      if (e.key === "Escape") closeOffer(button);
    });
  });

  // « Commander un bon cadeau » écrit la demande dans le message, sans effacer ce qui y est déjà.
  const requestMessage = $("demandeMessage");
  document.querySelector("[data-prefill]").addEventListener("click", e => {
    if (!requestMessage.value.trim()) requestMessage.value = e.currentTarget.dataset.prefill;
  });

  const setStatus = (element, text, state = "") => {
    element.textContent = text;
    element.className = ("form__status " + state).trim();
  };

  const send = async (url, options, fallback) => {
    let response;
    let result;
    try {
      response = await fetch(url, { ...options, headers: { ...options.headers, Accept: "application/json" } });
      result = await response.json();
    } catch {
      throw new Error(fallback);
    }
    if (!response.ok || result?.ok !== true) throw Object.assign(new Error(result?.message || fallback), { code: result?.code });
    return result;
  };

  // Validation, verrouillage et erreurs communs aux deux formulaires.
  // Les champs sont bloqués pendant l'envoi, mais le statut reste accessible aux lecteurs d'écran.
  const bindAsyncForm = (form, status, { pending, fallback, ready = () => true, success, failure }) => {
    let busy = false;
    form.noValidate = true; // Sans JavaScript, le navigateur garde ses contrôles natifs.
    form.addEventListener("submit", async e => {
      e.preventDefault();
      if (busy || !ready()) return;
      if (!form.reportValidity()) {
        setStatus(status, messages.champs_obligatoires, "is-error");
        return;
      }

      const body = new FormData(form);
      const focused = document.activeElement;
      const submit = form.querySelector("[type='submit']");
      const submitLabel = submit.textContent;
      const controls = [...form.querySelectorAll("input, select, textarea, button")]
        .map(control => [control, control.disabled]);
      busy = true;
      form.setAttribute("aria-busy", "true");
      controls.forEach(([control]) => { control.disabled = true; });
      submit.textContent = pending;
      setStatus(status, pending, "is-pending");
      let result;
      let error;
      try {
        result = await send(form.action, { method: "POST", body }, fallback);
      } catch (reason) {
        error = reason;
      } finally {
        busy = false;
        controls.forEach(([control, disabled]) => { control.disabled = disabled; });
        submit.textContent = submitLabel;
        form.removeAttribute("aria-busy");
      }
      // Le calendrier peut être reconstruit par ces callbacks : restaurer les anciens champs avant.
      if (error) {
        if (failure) failure(error);
        else setStatus(status, error.message, "is-error");
      } else success(result);
      if (!form.hidden && form.contains(focused) && document.activeElement === document.body) {
        focused.focus({ preventScroll: true });
      }
    });
  };

  bindAsyncForm(form, status, {
    pending: messages.envoi_en_cours,
    fallback: messages.envoi_echoue,
    success: result => {
      form.reset();
      setStatus(status, result.message, "is-ok");
    },
  });

  const booking = $("bookingForm");
  const bookingStatus = $("bookingStatus");
  const calendarRetry = $("calendarRetry");
  const when = $("bookingWhen");
  const calendar = $("calendar");
  const calendarGrid = $("calendarGrid");
  const slots = $("slots");
  const details = $("bookingDetails");
  const done = $("bookingDone");
  const monthName = new Intl.DateTimeFormat("fr-CH", { month: "long", year: "numeric" });
  const dayName = new Intl.DateTimeFormat("fr-CH", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
  const pad = number => String(number).padStart(2, "0");
  const toDate = key => {
    const [year, month, day = 1] = key.split("-").map(Number);
    return new Date(year, month - 1, day);
  };
  const shiftMonth = (month, step) => {
    const date = toDate(month);
    date.setMonth(date.getMonth() + step);
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}`;
  };
  const addMinutes = (time, minutes) => {
    const [hours, rest] = time.split(":").map(Number);
    const total = hours * 60 + rest + minutes;
    return `${pad(Math.floor(total / 60))}:${pad(total % 60)}`;
  };
  // « 9h00 », comme dans l'e-mail de confirmation.
  const hour = time => time.replace(/^0/, "").replace(":", "h");
  const agenda = { service: "", month: "", min: "", max: "", days: {}, date: "", view: 0 };
  const resetAgreements = () => details.querySelectorAll(".consent input").forEach(input => { input.checked = false; });
  const hideDetails = () => {
    details.hidden = details.disabled = true;
    booking.elements.conditions_version.value = "";
    resetAgreements();
  };
  const showIntake = service => {
    details.querySelectorAll("[data-intake]").forEach(fieldset => {
      fieldset.hidden = fieldset.disabled = fieldset.dataset.intake !== service.split(".")[0];
    });
    details.querySelectorAll("[data-terms]").forEach(section => {
      section.hidden = section.dataset.terms !== service;
      if (!section.hidden) booking.elements.conditions_version.value = section.dataset.version;
    });
  };

  // Une requête par mois pour toutes les prestations, relue après 30 secondes pour suivre les changements de l'agenda.
  const monthRequests = new Map();
  const fetchMonth = month => {
    if (!monthRequests.has(month) || Date.now() - monthRequests.get(month).at > 30000) {
      const entry = { at: Infinity };
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 15000);
      entry.promise = send(`api/availability.php?${new URLSearchParams({ month })}`, { signal: controller.signal }, messages.agenda_indisponible).then(result => {
        entry.at = Date.now();
        if (monthRequests.get(month) === entry) monthRequests.set(result.month, entry);
        return result;
      }).finally(() => clearTimeout(timeout));
      monthRequests.set(month, entry);
      entry.promise.catch(() => {
        for (const [key, cached] of monthRequests) if (cached === entry) monthRequests.delete(key);
      });
    }
    return monthRequests.get(month).promise;
  };
  const forgetMonths = () => monthRequests.clear();

  const press = (container, value, key) => container.querySelectorAll(`[data-${key}]`)
    .forEach(button => button.setAttribute("aria-pressed", button.dataset[key] === value));

  const clearSelection = () => {
    agenda.date = "";
    booking.elements.date.value = booking.elements.time.value = "";
    slots.innerHTML = "";
    hideDetails();
  };

  const renderCalendar = () => {
    const first = toDate(agenda.month);
    $("calendarTitle").textContent = monthName.format(first);
    calendar.querySelector("[data-step='-1']").disabled = agenda.month <= agenda.min;
    calendar.querySelector("[data-step='1']").disabled = agenda.month >= agenda.max;

    const cells = ["lu", "ma", "me", "je", "ve", "sa", "di"]
      .map(day => `<span class="calendar__dow" aria-hidden="true">${day}</span>`);
    cells.push(...Array((first.getDay() + 6) % 7).fill("<span></span>"));
    const length = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();
    for (let day = 1; day <= length; day++) {
      const key = `${agenda.month}-${pad(day)}`;
      const open = key in agenda.days;
      cells.push(`<button type="button" class="calendar__day" data-date="${key}" aria-pressed="${key === agenda.date}"
        aria-label="${dayName.format(toDate(key))}${open ? "" : ", indisponible"}"${open ? "" : " disabled"}>${day}</button>`);
    }
    calendarGrid.innerHTML = cells.join("");
  };

  const selectDate = date => {
    agenda.date = date;
    press(calendarGrid, date, "date");
    booking.elements.date.value = date;
    booking.elements.time.value = "";
    hideDetails();
    slots.innerHTML = `<p class="slots__day">${dayName.format(toDate(date))}</p>` + agenda.days[date]
      .map(time => `<button type="button" class="slot" data-time="${time}" aria-pressed="false">${hour(time)}</button>`)
      .join("");
  };

  const selectTime = time => {
    resetAgreements();
    press(slots, time, "time");
    booking.elements.time.value = time;
    const service = booking.querySelector("input[name='service']:checked");
    const when = service.dataset.duration
      ? `de ${hour(time)} à ${hour(addMinutes(time, Number(service.dataset.duration)))}`
      : `à ${hour(time)}`;
    showIntake(service.value);
    const terms = details.querySelector("[data-terms]:not([hidden])");
    $("bookingRecapService").textContent = service.dataset.label.replaceAll(" · ", ", ");
    $("bookingRecapDate").textContent = `${dayName.format(toDate(agenda.date))}, ${when}`;
    $("bookingRecapMeta").textContent = `${terms.dataset.format}, ${terms.dataset.price}`;
    details.hidden = details.disabled = false;
    $("bookingIntakeTitle").focus({ preventScroll: true });
    $("bookingIntakeTitle").scrollIntoView({ block: "start", behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth" });
  };

  const showMonth = async (month, notice = "") => {
    const view = ++agenda.view;
    // L'ancien créneau ne doit pas rester réservable pendant le chargement d'un autre mois.
    clearSelection();
    calendarRetry.hidden = true;
    calendar.querySelectorAll("button").forEach(button => { button.disabled = true; });
    calendar.setAttribute("aria-busy", "true");
    setStatus(bookingStatus, notice || messages.recherche, notice ? "is-error" : "is-pending");
    try {
      const result = await fetchMonth(month);
      if (view !== agenda.view) return;
      Object.assign(agenda, { month: result.month, min: result.min, max: result.max, days: result.services[agenda.service] ?? {} });
      renderCalendar();
      const empty = !Object.keys(agenda.days).length && messages.aucun_creneau;
      setStatus(bookingStatus, notice || empty || "", notice && "is-error");
      if (result.month < result.max) fetchMonth(shiftMonth(result.month, 1));
    } catch (error) {
      if (view === agenda.view) {
        setStatus(bookingStatus, error.message, "is-error");
        calendarRetry.hidden = false;
      }
    } finally {
      if (view === agenda.view) calendar.setAttribute("aria-busy", "false");
    }
  };
  calendarRetry.addEventListener("click", () => showMonth(agenda.month));

  // Préparer le mois courant dès l'accueil, puis le rafraîchir à l'approche du formulaire.
  // Tous ces déclencheurs partagent la même requête et le même cache de 30 secondes.
  const preloadCalendar = () => { fetchMonth(""); };
  if ("requestIdleCallback" in window) requestIdleCallback(preloadCalendar, { timeout: 1000 });
  else setTimeout(preloadCalendar, 250);
  document.querySelectorAll("a[href='#rendez-vous'], [data-service]").forEach(link => {
    ["pointerenter", "focus", "pointerdown"].forEach(event => link.addEventListener(event, preloadCalendar, { passive: true }));
  });
  observe([$("rendez-vous")], { rootMargin: "600px 0px" }, (section, io) => {
    io.unobserve(section);
    preloadCalendar();
  });

  // Prestation à formules : son nom ouvre la liste des formules, et reste marqué quand l'une d'elles est choisie.
  const groups = booking.querySelectorAll(".choices__groupe");
  const groupOf = value => booking.querySelector(`.choices__groupe[aria-controls='formules-${value.split(".")[0]}']`);
  const setGroup = (button, open) => {
    button.setAttribute("aria-expanded", open);
    $(button.getAttribute("aria-controls")).hidden = !open;
  };
  groups.forEach(button => button.addEventListener("click", () => setGroup(button, button.getAttribute("aria-expanded") !== "true")));

  booking.querySelectorAll("input[name='service']").forEach(radio => radio.addEventListener("change", () => {
    agenda.service = radio.value;
    groups.forEach(button => button.classList.toggle("is-chosen", button === groupOf(radio.value)));
    clearSelection();
    when.hidden = false;
    showMonth(agenda.month);
  }));

  calendar.addEventListener("click", e => {
    const step = e.target.closest("[data-step]");
    const day = e.target.closest(".calendar__day");
    if (step) showMonth(shiftMonth(agenda.month, Number(step.dataset.step)));
    else if (day) selectDate(day.dataset.date);
  });

  slots.addEventListener("click", e => {
    const slot = e.target.closest(".slot");
    if (slot) selectTime(slot.dataset.time);
  });

  // Formulaire vierge, formules refermées : pour un nouveau rendez-vous après une réservation.
  const resetBooking = () => {
    booking.reset();
    booking.elements.request_id.value = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, "0")).join("");
    clearSelection();
    agenda.service = "";
    groups.forEach(button => {
      button.classList.remove("is-chosen");
      setGroup(button, false);
    });
    when.hidden = true;
    calendarRetry.hidden = true;
    done.hidden = true;
    booking.hidden = false;
    setStatus(bookingStatus, "");
  };

  // « Réserver » sur une carte : coche la prestation ou la formule. Pour une prestation à formules, ouvre leur liste.
  document.querySelectorAll("[data-service]").forEach(link => link.addEventListener("click", () => {
    if (booking.hidden) resetBooking();
    const group = groupOf(link.dataset.service);
    if (group) setGroup(group, true);
    const radio = booking.querySelector(`input[name='service'][value='${link.dataset.service}']`);
    if (radio && !radio.checked) {
      radio.checked = true;
      radio.dispatchEvent(new Event("change"));
    }
  }));

  bindAsyncForm(booking, bookingStatus, {
    pending: messages.reservation_en_cours,
    fallback: messages.reservation_echouee,
    ready: () => Boolean(booking.elements.time.value),
    success: result => {
      forgetMonths();
      const confirmation = result.email_pending ? messages.reservation_email_en_cours
        : result.email_sent === false ? messages.reservation_sans_email : messages.reservation_confirmee;
      $("bookingDoneText").textContent = confirmation
        .replaceAll("{prestation}", result.service)
        .replaceAll("{date}", result.when)
        .replaceAll("{email}", booking.elements.email.value)
        + (result.visio ? "" : " " + messages.reservation_confirmee_telephone);
      setStatus(bookingStatus, "");
      booking.hidden = true;
      done.hidden = false;
      done.focus();
    },
    failure: error => {
      if (error.code === "slot_taken") {
        forgetMonths();
        showMonth(agenda.month, error.message);
      } else {
        setStatus(bookingStatus, error.message, "is-error");
      }
    },
  });

  $("bookingAgain").addEventListener("click", resetBooking);
});
