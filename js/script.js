document.addEventListener("DOMContentLoaded", () => {
  const $ = id => document.getElementById(id);
  const messages = JSON.parse($("messages").textContent);
  const header = $("header");
  const nav = $("nav");
  const burger = $("burger");
  const toTop = $("toTop");
  const form = $("rdvForm");
  const status = $("formStatus");
  const formatField = $("formatAuto");
  const prestations = form.querySelectorAll("input[name='prestation[]']");

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

  const setStatus = (element, text, state = "") => {
    element.textContent = text;
    element.className = ("form__status " + state).trim();
  };

  const send = async (url, options, fallback) => {
    let result;
    try {
      const response = await fetch(url, { ...options, headers: { Accept: "application/json" } });
      result = await response.json();
    } catch {
      throw new Error(fallback);
    }
    if (!result.ok) throw Object.assign(new Error(result.message || fallback), { code: result.code });
    return result;
  };

  form.addEventListener("submit", async e => {
    e.preventDefault();
    if (!form.reportValidity()) {
      setStatus(status, messages.champs_obligatoires, "is-error");
      return;
    }

    const submit = form.querySelector(".form__submit");
    submit.disabled = true;
    setStatus(status, messages.envoi_en_cours);
    try {
      const result = await send(form.action, { method: "POST", body: new FormData(form) }, messages.envoi_echoue);
      form.reset();
      syncFormat();
      setStatus(status, result.message, "is-ok");
    } catch (error) {
      setStatus(status, error.message, "is-error");
    } finally {
      submit.disabled = false;
    }
  });

  const booking = $("bookingForm");
  const bookingStatus = $("bookingStatus");
  const when = $("bookingWhen");
  const calendar = $("calendar");
  const calendarGrid = $("calendarGrid");
  const slots = $("slots");
  const details = $("bookingDetails");
  const done = $("bookingDone");
  const monthName = new Intl.DateTimeFormat("fr-CH", { month: "long", year: "numeric" });
  const dayName = new Intl.DateTimeFormat("fr-CH", { weekday: "long", day: "numeric", month: "long" });
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
  const hour = time => time.replace(":", "h");
  const agenda = { service: "", month: "", min: "", max: "", days: {}, date: "", view: 0 };

  // Une requête par mois pour toutes les prestations, relue après 30 secondes pour suivre les changements de l'agenda.
  const monthRequests = {};
  const fetchMonth = month => {
    if (!monthRequests[month] || Date.now() - monthRequests[month].at > 30000) {
      const request = send(`api/availability.php?${new URLSearchParams({ month })}`, {}, messages.agenda_indisponible);
      monthRequests[month] = request.then(result => {
        monthRequests[result.month] = monthRequests[month];
        return result;
      });
      monthRequests[month].at = Date.now();
      monthRequests[month].catch(() => delete monthRequests[month]);
    }
    return monthRequests[month];
  };
  const forgetMonths = () => Object.keys(monthRequests).forEach(month => delete monthRequests[month]);

  const press = (container, value, key) => container.querySelectorAll(`[data-${key}]`)
    .forEach(button => button.setAttribute("aria-pressed", button.dataset[key] === value));

  const clearSelection = () => {
    agenda.date = "";
    booking.elements.date.value = booking.elements.time.value = "";
    slots.innerHTML = "";
    details.hidden = true;
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
    details.hidden = true;
    slots.innerHTML = `<p class="slots__day">${dayName.format(toDate(date))}</p>` + agenda.days[date]
      .map(time => `<button type="button" class="slot" data-time="${time}" aria-pressed="false">${hour(time)}</button>`)
      .join("");
  };

  const selectTime = time => {
    press(slots, time, "time");
    booking.elements.time.value = time;
    const service = booking.querySelector("input[name='service']:checked");
    const end = addMinutes(time, Number(service.dataset.duration));
    $("bookingRecap").textContent = `${service.dataset.label} · ${dayName.format(toDate(agenda.date))}, de ${hour(time)} à ${hour(end)}`;
    details.hidden = false;
  };

  const showMonth = async (month, notice = "") => {
    const view = ++agenda.view;
    calendar.setAttribute("aria-busy", "true");
    setStatus(bookingStatus, notice || messages.recherche, notice && "is-error");
    try {
      const result = await fetchMonth(month);
      if (view !== agenda.view) return;
      Object.assign(agenda, { month: result.month, min: result.min, max: result.max, days: result.services[agenda.service] ?? {} });
      renderCalendar();
      if (agenda.date in agenda.days) selectDate(agenda.date);
      else clearSelection();
      const empty = !Object.keys(agenda.days).length && messages.aucun_creneau;
      setStatus(bookingStatus, notice || empty || "", notice && "is-error");
      if (result.month < result.max) fetchMonth(shiftMonth(result.month, 1));
    } catch (error) {
      if (view === agenda.view) setStatus(bookingStatus, error.message, "is-error");
    } finally {
      if (view === agenda.view) calendar.setAttribute("aria-busy", "false");
    }
  };

  // L'agenda est lu dès que la section approche : au clic sur une prestation, il est déjà là.
  observe([$("rendez-vous")], { rootMargin: "600px 0px" }, (section, io) => {
    io.unobserve(section);
    fetchMonth("");
  });

  booking.querySelectorAll("input[name='service']").forEach(radio => radio.addEventListener("change", () => {
    agenda.service = radio.value;
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

  document.querySelectorAll("[data-service]").forEach(link => link.addEventListener("click", () => {
    const radio = booking.querySelector(`input[name='service'][value='${link.dataset.service}']`);
    if (!radio.checked) {
      radio.checked = true;
      radio.dispatchEvent(new Event("change"));
    }
  }));

  booking.addEventListener("submit", async e => {
    e.preventDefault();
    if (!booking.elements.time.value) return;
    if (!booking.reportValidity()) {
      setStatus(bookingStatus, messages.champs_obligatoires, "is-error");
      return;
    }

    const submit = booking.querySelector(".form__submit");
    submit.disabled = true;
    setStatus(bookingStatus, messages.reservation_en_cours);
    try {
      const result = await send(booking.action, { method: "POST", body: new FormData(booking) }, messages.reservation_echouee);
      forgetMonths();
      $("bookingDoneText").textContent = messages.reservation_confirmee
        .replaceAll("{prestation}", result.service)
        .replaceAll("{date}", result.when)
        .replaceAll("{email}", booking.elements.email.value)
        + (result.visio ? "" : " " + messages.reservation_confirmee_telephone);
      setStatus(bookingStatus, "");
      booking.hidden = true;
      done.hidden = false;
      done.focus();
    } catch (error) {
      if (error.code === "slot_taken") {
        forgetMonths();
        showMonth(agenda.month, error.message);
      } else {
        setStatus(bookingStatus, error.message, "is-error");
      }
    } finally {
      submit.disabled = false;
    }
  });

  $("bookingAgain").addEventListener("click", () => {
    booking.reset();
    clearSelection();
    agenda.service = "";
    when.hidden = true;
    done.hidden = true;
    booking.hidden = false;
  });
});
