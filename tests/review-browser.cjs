// Usage : node tests/review-browser.cjs
// PLAYWRIGHT_MODULE permet de réutiliser une installation de playwright-core.
// PHP_BIN permet de choisir PHP ; REVIEW_BROWSERS=chromium,firefox,webkit.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const net = require('node:net');
const { spawn, spawnSync } = require('node:child_process');
const playwright = require(process.env.PLAYWRIGHT_MODULE || 'playwright-core');
const axe = require('axe-core');
const root = path.resolve(__dirname, '..');
const output = fs.mkdtempSync(path.join(os.tmpdir(), 'elo-site-review-'));
const fixture = path.join(output, 'www');
const failures = [];
const observations = [];
const check = (ok, message) => { if (!ok) failures.push(message); };

function copySite(source, dest) {
  fs.mkdirSync(dest, { recursive: true });
  for (const name of fs.readdirSync(source)) {
    if (/^(app|textes|css|js|fonts|images)$/.test(name)) {
      fs.cpSync(path.join(source, name), path.join(dest, name), {
        recursive: true, filter: file => !/^secrets(?:\.|$)/.test(path.basename(file)),
      });
    } else if (/\.php$/.test(name)) fs.copyFileSync(path.join(source, name), path.join(dest, name));
  }
  fs.mkdirSync(path.join(dest, 'storage'), { recursive: true });
  fs.writeFileSync(path.join(dest, 'app/secrets.php'), "<?php return ['app_key' => 'local-review-fixture-key-without-any-real-secret'];\n");
}

async function freePort() {
  const server = net.createServer();
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const port = server.address().port;
  await new Promise(resolve => server.close(resolve));
  return port;
}

async function layout(page, label) {
  const result = await page.evaluate(() => {
    const width = document.documentElement.clientWidth;
    const overflow = [...document.querySelectorAll('body *')].filter(el => {
      if (el.closest('svg, .honeypot, .sky, .soon__decor') || !el.checkVisibility()) return false;
      const r = el.getBoundingClientRect();
      return r.width > 0 && (r.right > width + 1 || r.left < -1);
    }).map(el => `${el.tagName}.${el.className}`).slice(0, 8);
    return { width, scrollWidth: document.documentElement.scrollWidth, overflow };
  });
  check(result.scrollWidth <= result.width + 1, `${label}: débordement page ${JSON.stringify(result)}`);
  check(!result.overflow.length, `${label}: éléments hors écran ${result.overflow.join(', ')}`);
}

async function accessibility(page, label) {
  await page.evaluate(axe.source);
  const issues = await page.evaluate(async () => (await axe.run(document, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] },
  })).violations.map(v => ({ rule: v.id, elements: v.nodes.map(n => n.target.join(' ')) })));
  check(!issues.length, `${label}: accessibilité ${JSON.stringify(issues)}`);
}

async function setupPage(context) {
  const page = await context.newPage();
  page.setDefaultTimeout(6000);
  page.on('pageerror', error => failures.push(`JavaScript: ${error.message}`));
  page.on('response', response => {
    if (response.status() >= 400 && !response.url().includes('/api/')) failures.push(`HTTP ${response.status()}: ${new URL(response.url()).pathname}`);
  });
  // Aucun appel à Google ni envoi réel : toutes les API sont interceptées.
  await page.route('**/api/**', async route => {
    const url = new URL(route.request().url());
    if (url.pathname.endsWith('availability.php')) {
      const month = url.searchParams.get('month') || '2026-10';
      const ids = await page.locator('[name=service][value]').evaluateAll(els => els.map(el => el.value));
      await route.fulfill({ json: { ok: true, month, min: '2026-10', max: '2026-12', services: Object.fromEntries(ids.map(id => [id, { [`${month}-20`]: ['09:00', '14:30'] }])) } });
    } else await route.fulfill({ json: { ok: true, message: 'Demande de test reçue.', service: 'Séance de test', when: '20 octobre 2026, 9h00', visio: false, email_sent: true } });
  });
  return page;
}

async function fillPerson(page, form) {
  for (const [name, value] of Object.entries({ prenom: 'Camille', nom: 'Exemple', email: 'camille@example.test', telephone: '+41790000000', naissance: '1990-01-01' })) {
    const input = page.locator(`${form} [name="${name}"]`);
    if (await input.count()) await input.fill(value);
  }
  for (const el of await page.locator(`${form} textarea:visible`).all()) await el.fill('Réponse fictive pour vérifier le formulaire.');
  for (const el of await page.locator(`${form} select:visible`).all()) await el.selectOption({ index: 1 });
  for (const el of await page.locator(`${form} input[type=checkbox]:visible`).all()) await el.check();
}

async function review(browser, base, name) {
  const context = await browser.newContext({ reducedMotion: 'reduce' });
  const page = await setupPage(context);
  const widths = name === 'chromium' ? [320, 360, 375, 390, 430, 540, 600, 768, 820, 980, 1024, 1100, 1101, 1280, 1440, 1920, 2560] : [320, 390, 768, 1024, 1440];
  for (const pathname of ['/', '/mentions-legales', '/confidentialite', '/site/', '/site/mentions-legales', '/site/confidentialite', '/site/annuler', '/site/bientot']) {
    await page.goto(base + pathname);
    await page.evaluate(() => document.fonts.ready);
    const assets = await page.locator('img').evaluateAll(imgs => imgs.filter(img => !img.complete || !img.naturalWidth).map(img => img.getAttribute('src')));
    check(!assets.length, `${name} ${pathname}: images manquantes ${assets}`);
    check(await page.evaluate(() => document.fonts.check('16px Montserrat') && document.fonts.check('32px "Cormorant Garamond"')), `${name} ${pathname}: polices non chargées`);
    if (name === 'chromium') await accessibility(page, pathname);
    for (const width of widths) {
      await page.setViewportSize({ width, height: 900 });
      await layout(page, `${name} ${pathname} ${width}px`);
      if (name === 'chromium' && ['/', '/site/', '/site/bientot'].includes(pathname) && [320, 1440].includes(width)) {
        await page.evaluate(() => scrollTo(0, 0));
        await page.screenshot({ path: path.join(output, `${pathname === '/' ? 'attente' : pathname === '/site/' ? 'accueil' : 'bientot'}-${width}.png`) });
      }
    }
  }
  await page.goto(base + '/site/');
  for (const viewport of [{ width: 320, height: 568 }, { width: 667, height: 375 }, { width: 768, height: 1024 }, { width: 1024, height: 600 }, { width: 1440, height: 900 }]) {
    await page.setViewportSize(viewport);
    if (viewport.width <= 1100) {
      await page.locator('#burger').click();
      check(await page.locator('#burger').getAttribute('aria-expanded') === 'true', `${name}: menu ouvert`);
      await layout(page, `${name} menu ${viewport.width}`);
      await page.keyboard.press('Escape');
      check(await page.locator('#burger').getAttribute('aria-expanded') === 'false', `${name}: fermeture menu Échap`);
    }
    for (const button of await page.locator('.tag--offre').all()) {
      await button.click();
      const panel = page.locator('#' + await button.getAttribute('aria-controls'));
      await layout(page, `${name} formule ${viewport.width}`);
      await panel.locator('[data-close]').click();
    }
    await page.locator('#bookingForm label:has([name=service][value=tirage])').click();
    await page.locator('.calendar__day:not(:disabled)').first().click();
    await page.locator('.slot').first().click();
    await layout(page, `${name} questionnaire ${viewport.width}`);
    if (name === 'chromium' && viewport.width === 320) await accessibility(page, 'questionnaire mobile');
    await page.locator('.booking__conditions:visible summary').click();
    await layout(page, `${name} conditions ${viewport.width}`);
    await page.locator('.booking__conditions:visible summary').click();
    if (name === 'chromium' && [320, 768, 1440].includes(viewport.width)) {
      await page.screenshot({ path: path.join(output, `questionnaire-${viewport.width}.png`) });
    }
  }
  // Toutes les formules activent uniquement leur questionnaire et leurs conditions.
  const serviceIds = await page.locator('#bookingForm [name=service][value]').evaluateAll(inputs => inputs.map(input => input.value));
  for (const id of serviceIds) {
    const radio = page.locator(`#bookingForm [name=service][value="${id}"]`);
    if (id.includes('.')) {
      const group = page.locator(`[aria-controls="formules-${id.split('.')[0]}"]`);
      if (await group.getAttribute('aria-expanded') !== 'true') await group.click();
    }
    await radio.locator('..').click();
    await page.locator('.calendar__day:not(:disabled)').first().click();
    await page.locator('.slot').first().click();
    check(await page.locator('[data-intake]:visible').getAttribute('data-intake') === id.split('.')[0], `${name}: questionnaire ${id}`);
    check(await page.locator('[data-terms]:visible').getAttribute('data-terms') === id, `${name}: conditions ${id}`);
    await fillPerson(page, '#bookingForm');
    await page.locator('.slot').last().click();
    check(await page.locator('#bookingDetails .consent input:checked').count() === 0, `${name}: accords réinitialisés après changement du créneau`);
  }
  await fillPerson(page, '#bookingForm');
  await page.locator('#bookingForm [type=submit]').click();
  await page.locator('#bookingDone:visible').waitFor();
  await page.locator('#bookingAgain').click();
  check(await page.locator('#bookingWhen').isHidden(), `${name}: nouveau rendez-vous vierge`);
  await fillPerson(page, '#rdvForm');
  await page.locator('#rdvForm [type=submit]').click();
  await page.waitForFunction(() => document.querySelector('#formStatus').classList.contains('is-ok'));

  // Texte doublé (reflow) : navigation, cartes, formulaires.
  await page.goto(base + '/site/');
  await page.addStyleTag({ content: 'html { font-size: 200%; }' });
  for (const width of [320, 768, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    await layout(page, `${name} texte 200% ${width}`);
  }
  await context.close();
  const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 320, height: 568 } });
  const nojs = await plain.newPage();
  await nojs.goto(base + '/site/');
  check(await nojs.locator('#nav').isVisible(), `${name}: menu sans JS`);
  check(await nojs.locator('.presta__offre').first().isVisible(), `${name}: formules sans JS`);
  check(await nojs.locator('#rdvForm [type=submit]').isVisible(), `${name}: contact sans JS`);
  await layout(nojs, `${name} sans JS`);
  await plain.close();
  if (name === 'chromium') {
    const failedScript = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const fallback = await setupPage(failedScript);
    await fallback.route('**/js/script.js?*', route => route.abort());
    await fallback.goto(base + '/site/');
    check(await fallback.locator('html').evaluate(el => !el.classList.contains('js')), 'Echec du script: mode sans JS restaure');
    check(await fallback.locator('#nav').isVisible(), 'Echec du script: navigation accessible');
    check(await fallback.locator('.hero__text').evaluate(el => getComputedStyle(el).opacity === '1'), 'Echec du script: contenu visible');
    await failedScript.close();
    await bookingPerformance(browser, base);
    await networkScenarios(browser, base);
    await timeoutScenarios(browser, base);
    const visual = await browser.newContext({ reducedMotion: 'no-preference' });
    const preview = await setupPage(visual);
    for (const width of [320, 1440]) {
      await preview.setViewportSize({ width, height: 900 });
      await preview.goto(base + '/site/');
      await preview.waitForFunction(() => document.querySelector('.hero__text').classList.contains('is-in'));
      await preview.evaluate(() => Promise.all(document.getAnimations().filter(a => a.effect.getTiming().iterations !== Infinity).map(a => a.finished.catch(() => {}))));
      await preview.screenshot({ path: path.join(output, `accueil-anime-${width}.png`) });
      check(await preview.locator('.hero__text').evaluate(el => getComputedStyle(el).opacity === '1'), 'Accueil visible après animation');
    }
    await visual.close();
  }
  observations.push(`${name}: ${widths.length} largeurs, 8 pages, menus, formules, réservation/contact simulés, texte 200%, sans JS`);
}

async function bookingPerformance(browser, base) {
  const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 900 } });
  const page = await setupPage(context);
  const months = [];
  let release;
  const response = new Promise(resolve => { release = resolve; });
  await page.route('**/api/availability.php?**', async route => {
    months.push(new URL(route.request().url()).searchParams.get('month'));
    await response;
    await route.fallback();
  });
  await page.goto(base + '/site/');
  await page.waitForRequest('**/api/availability.php?**').catch(() => check(months.length > 0, 'Agenda: préchargement dès l’accueil'));
  check(await page.evaluate(() => scrollY === 0), 'Agenda: préchargement avant tout défilement');
  await page.locator('a[href="#rendez-vous"]').first().dispatchEvent('pointerenter');
  await page.locator('#bookingForm label:has([value=tirage])').click();
  check(months.filter(month => month === '').length === 1, 'Agenda: une seule requête pendant le préchargement et le choix');
  release();
  await page.locator('.calendar__day:not(:disabled)').first().waitFor();
  await page.locator('#bookingForm label:has([value=pendule])').click();
  await page.locator('.calendar__day:not(:disabled)').first().click();
  check(months.filter(month => month === '' || month === '2026-10').length === 1, 'Agenda: cache partagé entre prestations');
  await page.locator('.slot').first().click();
  await fillPerson(page, '#bookingForm');
  let posts = 0;
  let finish;
  const bookingResponse = new Promise(resolve => { finish = resolve; });
  await page.route('**/api/booking.php', async route => {
    posts++;
    await bookingResponse;
    await route.fulfill({ json: { ok: true, service: 'Séance de test', when: '20 octobre 2026, 9h00', visio: false, email_sent: false, email_pending: true } });
  });
  await page.locator('#bookingForm [type=submit]').click();
  await page.waitForFunction(() => document.querySelector('#bookingForm').getAttribute('aria-busy') === 'true');
  check((await page.locator('#bookingForm [type=submit]').textContent()).includes('en cours'), 'Réservation: attente visible sur le bouton');
  await page.locator('#bookingForm').evaluate(form => form.requestSubmit());
  await page.locator('[data-service="tirage"]').first().dispatchEvent('click');
  check(await page.locator('#bookingForm [name=service]:checked').inputValue() === 'pendule', 'Reservation: prestation conservee pendant l’envoi');
  finish();
  await page.locator('#bookingDone:visible').waitFor();
  check(posts === 1, 'Réservation: une seule requête malgré une double soumission');
  check((await page.locator('#bookingDoneText').textContent()).includes('en cours d’envoi'), 'Réservation: e-mail en attente annoncé sans faux échec');
  await page.locator('#bookingAgain').click();
  await page.locator('#bookingForm label:has([value=tirage])').click();
  await page.locator('.calendar__day:not(:disabled)').first().waitFor();
  check(months.filter(month => month === '' || month === '2026-10').length === 2, 'Agenda: disponibilités relues après réservation');
  await context.close();
  observations.push('Préchargement dès l’accueil, requêtes partagées, cache invalidé après réservation, double clic et confirmation avec e-mail différé');
}

async function networkScenarios(browser, base) {
  const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 390, height: 844 } });
  const page = await setupPage(context);
  await page.goto(base + '/site/');
  await fillPerson(page, '#rdvForm');
  let calls = 0;
  let release;
  const response = new Promise(resolve => { release = resolve; });
  await page.route('**/api/contact.php', async route => {
    calls++;
    await response;
    await route.fulfill({ status: 500, json: { ok: true, message: 'Erreur de test' } });
  });
  await page.locator('#rdvForm [type=submit]').click();
  await page.waitForFunction(() => document.querySelector('#rdvForm').getAttribute('aria-busy') === 'true');
  check(await page.locator('#rdvForm [name=email]').isDisabled(), 'Contact: saisies bloquées pendant l’envoi');
  check(await page.locator('#formStatus').evaluate(el => !el.closest('[inert]')), 'Contact: statut accessible pendant l’envoi');
  await page.locator('#rdvForm').evaluate(form => form.requestSubmit());
  release();
  await page.waitForFunction(() => document.querySelector('#formStatus').classList.contains('is-error'));
  check(calls === 1, 'Contact: un seul POST malgré une double soumission');
  check(await page.locator('#rdvForm [name=email]').inputValue() === 'camille@example.test', 'Contact: saisie conservée après erreur');
  check(await page.locator('#rdvForm [name=email]').isEnabled(), 'Contact: déverrouillage après erreur');
  await page.unroute('**/api/contact.php');
  await page.locator('#rdvForm [type=submit]').click();
  await page.waitForFunction(() => document.querySelector('#formStatus').classList.contains('is-ok'));

  await page.route('**/api/availability.php?**', route => route.fulfill({ status: 503, json: { ok: false, message: 'Agenda de test indisponible' } }));
  await page.goto(base + '/site/');
  await page.locator('#bookingForm label:has([value=tirage])').click();
  await page.locator('#calendarRetry:visible').waitFor();
  await page.unroute('**/api/availability.php?**');
  await page.locator('#calendarRetry').click();
  await page.locator('.calendar__day:not(:disabled)').first().click();
  await page.locator('.slot').first().click();
  await fillPerson(page, '#bookingForm');
  await page.route('**/api/booking.php', route => route.fulfill({ status: 409, json: { ok: false, code: 'slot_taken', message: 'Créneau de test déjà pris' } }));
  await page.locator('#bookingForm [type=submit]').click();
  await page.locator('#bookingDetails[hidden]').waitFor({ state: 'attached' });
  await page.waitForFunction(() => document.querySelector('#calendar').getAttribute('aria-busy') === 'false');
  check(await page.locator('#bookingForm [name=time]').inputValue() === '', 'Réservation: ancien créneau effacé après conflit');
  check(await page.locator('.calendar__day:not(:disabled)').count() > 0, 'Réservation: calendrier récupéré après conflit');
  check(await page.locator('[data-step="-1"]').isDisabled(), 'Réservation: borne du calendrier conservée après conflit');
  await context.close();
  observations.push('Erreurs réseau/API : reprise agenda, HTTP 500, double soumission, saisies conservées, conflit de créneau');
}

// Mesures locales reproductibles ; Google est simule et Apache (gzip) n'est pas utilise.
// REVIEW_PERFORMANCE_ONLY=1 node tests/review-browser.cjs
async function performanceReview(browser, base) {
  // Une reponse HTTP locale garde la limitation reseau CDP effective (sans page.route).
  fs.mkdirSync(path.join(fixture, 'site/api'), { recursive: true });
  fs.writeFileSync(path.join(fixture, 'site/api/availability.php'), '<?php header("Content-Type: application/json"); echo json_encode(["ok" => true, "month" => "2026-10", "min" => "2026-10", "max" => "2026-12", "services" => (object) []]);');
  for (const pathname of ['/', '/site/']) {
    const runs = [];
    for (let run = 0; run < 3; run++) {
      const context = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true });
      const page = await context.newPage();
      page.on('pageerror', error => failures.push(error.message));
      const cdp = await context.newCDPSession(page);
      await cdp.send('Network.enable');
      await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
      await cdp.send('Network.emulateNetworkConditionsByRule', { matchedNetworkConditions: [
        { urlPattern: '', latency: 100, downloadThroughput: 200000, uploadThroughput: 100000 },
      ] });
      await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 });
      await page.addInitScript(() => {
        window.auditPerformance = { lcp: 0, cls: 0, longTasksMs: 0, shifts: [] };
        new PerformanceObserver(list => list.getEntries().forEach(entry => {
          window.auditPerformance.lcp = entry.startTime;
        })).observe({ type: 'largest-contentful-paint', buffered: true });
        new PerformanceObserver(list => list.getEntries().forEach(entry => {
          if (!entry.hadRecentInput) {
            window.auditPerformance.cls += entry.value;
            window.auditPerformance.shifts.push({ at: entry.startTime, value: entry.value, nodes: entry.sources.map(source => source.node?.className || source.node?.nodeName) });
          }
        })).observe({ type: 'layout-shift', buffered: true });
        new PerformanceObserver(list => list.getEntries().forEach(entry => {
          window.auditPerformance.longTasksMs += entry.duration;
        })).observe({ type: 'longtask', buffered: true });
      });
      await page.goto(base + pathname);
      await page.evaluate(() => document.fonts.ready);
      await page.waitForTimeout(1200);
      runs.push(await page.evaluate(() => {
        const navigation = performance.getEntriesByType('navigation')[0];
        const resources = performance.getEntriesByType('resource').filter(entry => !entry.name.includes('/api/'));
        return { ...window.auditPerformance, ttfb: navigation.responseStart,
          bytes: navigation.encodedBodySize + resources.reduce((sum, entry) => sum + entry.encodedBodySize, 0),
          resources: resources.map(entry => ({ path: new URL(entry.name).pathname, bytes: entry.encodedBodySize })) };
      }));
      await context.close();
    }
    const median = key => runs.map(run => run[key]).sort((a, b) => a - b)[1];
    observations.push({ page: pathname, mobile: '390px, CPU x4, 1.6 Mbit/s, latence 100ms, cache froid, 3 passages',
      median: Object.fromEntries(['lcp', 'cls', 'longTasksMs', 'ttfb', 'bytes'].map(key => [key, median(key)])), resources: runs[1].resources, shifts: runs[1].shifts });
  }
}

async function timeoutScenarios(browser, base) {
  const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 900 } });
  const page = await setupPage(context);
  await page.clock.install();
  await page.goto(base + '/site/');
  for (const [form, status, api] of [['#rdvForm', '#formStatus', 'contact'], ['#bookingForm', '#bookingStatus', 'booking']]) {
    if (api === 'booking') {
      await page.locator('#bookingForm label:has([value=tirage])').click();
      await page.locator('.calendar__day:not(:disabled)').first().click();
      await page.locator('.slot').first().click();
    }
    await fillPerson(page, form);
    const requestId = await page.locator(`${form} [name=request_id]`).inputValue();
    const endpoint = `**/api/${api}.php`;
    let heldRoute;
    let received;
    const requestReceived = new Promise(resolve => { received = resolve; });
    await page.route(endpoint, route => { heldRoute = route; received(); });
    await page.locator(`${form} [type=submit]`).click();
    await requestReceived;
    await page.clock.runFor(30001);
    await page.locator(`${status}.is-error`).waitFor();
    check(await page.locator(`${form} [type=submit]`).isEnabled(), `${api}: bouton debloque apres 30 secondes`);
    check(await page.locator(`${form} [name=email]`).inputValue() === 'camille@example.test', `${api}: saisies conservees apres expiration`);
    check(await page.locator(`${form} [name=request_id]`).inputValue() === requestId, `${api}: identifiant conserve pour le nouvel essai`);
    check((await page.locator(status).textContent()).includes('peut-être'), `${api}: resultat incertain explique`);
    await heldRoute.abort().catch(() => {});
    await page.unroute(endpoint);
    const retried = page.waitForRequest(endpoint);
    await page.locator(`${form} [type=submit]`).click();
    check((await retried).postData().includes(requestId), `${api}: nouvel essai avec le meme identifiant`);
    if (api === 'contact') {
      await page.locator(`${status}.is-ok`).waitFor();
      check(await page.locator(`${form} [name=request_id]`).inputValue() !== requestId, 'Contact: nouvel identifiant apres succes');
    } else await page.locator('#bookingDone:visible').waitFor();
  }
  await context.close();
  observations.push('Expiration des deux formulaires apres 30 secondes : deverrouillage, saisies et identifiant conserves, nouvel essai reussi');
}

(async () => {
  copySite(root, fixture);
  copySite(path.join(root, 'site'), path.join(fixture, 'site'));
  const port = await freePort();
  const base = `http://127.0.0.1:${port}`;
  const php = process.env.PHP_BIN || path.join(root, 'php/php.exe');
  const phpChecks = spawnSync(php, ['-d', `extension_dir=${path.join(root, 'php/ext')}`, path.join(__dirname, 'review-php.php'), fixture], { windowsHide: true, encoding: 'utf8' });
  check(phpChecks.status === 0, `PHP : ${phpChecks.stdout} ${phpChecks.stderr}`);
  if (phpChecks.status === 0) observations.push(phpChecks.stdout.trim());
  const child = spawn(php, ['-d', `extension_dir=${path.join(root, 'php/ext')}`, '-S', `127.0.0.1:${port}`, '-t', fixture, path.join(__dirname, 'review-router.php')], { windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
  const log = fs.createWriteStream(path.join(output, 'php.log'));
  child.stdout.pipe(log); child.stderr.pipe(log);
  try {
    for (let attempt = 0; attempt < 40; attempt++) {
      try { await fetch(base); break; } catch { await new Promise(resolve => setTimeout(resolve, 100)); }
    }
    for (const name of (process.env.REVIEW_BROWSERS || 'chromium').split(',')) {
      const engine = name === 'edge' ? 'chromium' : name;
      const browser = await playwright[engine].launch({ headless: true, ...(['chromium', 'edge'].includes(name) ? { channel: name === 'edge' ? 'msedge' : 'chrome' } : {}) });
      console.log(`Vérification ${name} ${browser.version()}…`);
      try {
        if (process.env.REVIEW_PERFORMANCE_ONLY === '1') await performanceReview(browser, base);
        else await review(browser, base, name);
      } finally { await browser.close(); }
    }
  } catch (error) { failures.push(error.stack); }
  finally { child.kill(); log.end(); }
  const report = { output, observations, failures: [...new Set(failures)] };
  fs.writeFileSync(path.join(output, 'results.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
  process.exitCode = failures.length ? 1 : 0;
})();
