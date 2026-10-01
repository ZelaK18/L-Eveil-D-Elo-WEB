// node tests/responsive-browser.cjs [http://127.0.0.1:8765]
// Installer les moteurs avec playwright install firefox webkit ; Edge est utilisé sous Windows.
// BROWSER_ENGINE=chromium|firefox|webkit limite le contrôle à un moteur.
// RESPONSIVE_WIDTHS=320,768,1440 permet de cibler certaines largeurs.
// RESPONSIVE_SCREENSHOT_DIR conserve des captures ; AXE_MODULE active l'audit axe-core/playwright.
// Les API sont simulées : aucune réservation ni aucun e-mail réel.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { launchBrowser, availability } = require('./browser-helpers.cjs');
const base = process.argv[2] || 'http://127.0.0.1:8765';
const engines = process.env.BROWSER_ENGINE ? [process.env.BROWSER_ENGINE] : ['chromium', 'firefox', 'webkit'];
const screenshots = process.env.RESPONSIVE_SCREENSHOT_DIR;
if (screenshots) fs.mkdirSync(screenshots, { recursive: true });
const failures = [];
let checks = 0;

async function settle(page) {
  // Les callbacks de la page peuvent être suspendus lorsque JavaScript est désactivé.
  for (let attempt = 0; attempt < 50 && await page.evaluate(() => document.fonts.status !== 'loaded'); attempt++) {
    await page.waitForTimeout(100);
  }
  assert.equal(await page.evaluate(() => document.fonts.status), 'loaded', 'polices chargées');
  await page.waitForTimeout(50);
}

async function assertLayout(page, label) {
  await settle(page);
  const problems = await page.evaluate(() => {
    const issues = [];
    const viewport = document.documentElement.clientWidth;
    if (document.documentElement.scrollWidth > viewport + 1) issues.push(`page: ${document.documentElement.scrollWidth} > ${viewport}`);
    for (const el of document.querySelectorAll('body *')) {
      if (!el.getClientRects().length || getComputedStyle(el).visibility === 'hidden' || el.closest('svg, [aria-hidden="true"], .honeypot') || el.matches('option')) continue;
      const r = el.getBoundingClientRect();
      const name = el.id || (typeof el.className === 'string' && el.className) || el.tagName;
      if (r.left < -1 || r.right > viewport + 1) issues.push(`${name}: hors écran (${Math.round(r.left)}–${Math.round(r.right)})`);
      if (el.matches('p, h1, h2, h3, h4, h5, h6, button, legend, .choices span, .voucher__foot span') && el.clientWidth && el.scrollWidth > el.clientWidth + 2) issues.push(`${name}: texte débordant`);
    }
    const frame = document.querySelector('.voucher__frame');
    if (frame) {
      if (frame.offsetTop + frame.offsetHeight > frame.parentElement.clientHeight + 1) issues.push('bon cadeau : cadre débordant');
      for (const child of frame.children) {
        if (child.offsetTop < 0 || child.offsetTop + child.offsetHeight > frame.clientHeight + 1) issues.push('bon cadeau : contenu coupé');
      }
    }
    return [...new Set(issues)];
  });
  assert.deepEqual(problems, [], label);
  checks++;
}

async function screenshot(page, name, selector) {
  if (!screenshots) return;
  if (selector) await page.locator(selector).evaluate(el => el.scrollIntoView({ block: 'start', behavior: 'instant' }));
  await settle(page);
  await page.screenshot({ path: path.join(screenshots, `${name}.png`), animations: 'disabled' });
}

async function mockApi(page, state = {}) {
  await page.route('**/api/**', async route => {
    if (route.request().url().includes('/availability.php')) {
      if (state.offline) return route.fulfill({ status: 503, json: { ok: false, message: 'Agenda temporairement indisponible.' } });
      const month = new URL(route.request().url()).searchParams.get('month') || '2030-01';
      return route.fulfill({ json: availability(month) });
    }
    assert(route.request().url().includes('/contact.php'), 'API inattendue bloquée');
    state.contact = (state.contact || 0) + 1;
    return route.fulfill({ json: { ok: true, message: 'Votre demande a bien été envoyée.' } });
  });
}

async function openBooking(page) {
  const group = page.locator('.choices__groupe');
  await group.scrollIntoViewIfNeeded();
  await settle(page);
  await group.click();
  await page.locator('label:has(input[value="coaching.3"])').click();
  await page.locator('[data-date="2030-01-14"]').click();
  await page.locator('[data-time="10:00"]').click();
}

async function auditAccessibility(page) {
  if (!process.env.AXE_MODULE) return;
  const AxeBuilder = require(process.env.AXE_MODULE).default;
  const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
  assert.deepEqual(result.violations.map(v => ({ id: v.id, targets: v.nodes.map(n => n.target) })), [], 'accessibilité');
  checks++;
}

(async () => {
  for (const engine of engines) {
    const browser = await launchBrowser(engine);
    try {
      const widths = process.env.RESPONSIVE_WIDTHS ? process.env.RESPONSIVE_WIDTHS.split(',').map(Number) : engine === 'chromium'
        ? [280, 320, 360, 375, 390, 414, 430, 500, 600, 601, 760, 761, 768, 820, 980, 981, 1024, 1100, 1101, 1280, 1366, 1440, 1920, 2560, 3840]
        : [320, 390, 768, 1024, 1440, 2560];
      const viewports = [...widths.map(width => ({ width, height: 900 })), { width: 667, height: 375 }, { width: 844, height: 390 }, { width: 1366, height: 768 }];
      for (const viewport of viewports) {
        const label = `${engine} ${viewport.width}×${viewport.height}`;
        const context = await browser.newContext({ viewport, reducedMotion: 'reduce', hasTouch: engine !== 'firefox' && viewport.width <= 1024 });
        const page = await context.newPage();
        page.setDefaultTimeout(8000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        try {
          await mockApi(page);
          await page.goto(base);
          await assertLayout(page, `${label} accueil`);
          if ([320, 768, 1440, 2560].includes(viewport.width)) await screenshot(page, `${engine}-${viewport.width}-accueil`);
          if (await page.locator('#burger').isVisible()) {
            await page.locator('#burger').click();
            await assertLayout(page, `${label} menu`);
            const nav = await page.locator('#nav').boundingBox();
            assert(nav.y + nav.height <= viewport.height + 1, 'menu contenu dans la hauteur disponible');
            await page.locator('#nav a').last().click();
            assert.equal(await page.locator('#burger').getAttribute('aria-expanded'), 'false');
          }
          for (const offer of await page.locator('.tag--offre').all()) {
            const panel = page.locator('#' + await offer.getAttribute('aria-controls'));
            await offer.click();
            await assertLayout(page, `${label} détail prestation`);
            assert.equal(await panel.locator('[data-close]').evaluate(el => el === document.activeElement), true);
            await page.keyboard.press('Escape');
            assert.equal(await offer.getAttribute('aria-expanded'), 'false');
          }
          await openBooking(page);
          await assertLayout(page, `${label} questionnaire`);
          assert.equal(await page.locator('#bookingForm input[type="date"]').evaluate(el => parseFloat(getComputedStyle(el).fontSize) >= 16), true);
          if ([320, 768, 1440].includes(viewport.width)) await screenshot(page, `${engine}-${viewport.width}-formulaire`, '#bookingIntakeTitle');
          if (engine === 'chromium' && [320, 1440].includes(viewport.width)) await auditAccessibility(page);
          for (const route of ['mentions-legales.php', 'annuler.php']) {
            await page.goto(`${base}/${route}`);
            await assertLayout(page, `${label} ${route}`);
            if (engine === 'chromium' && viewport.width === 320) await auditAccessibility(page);
          }
          assert.deepEqual(errors, [], 'erreurs JavaScript');
          console.log(`OK ${label}`);
        } catch (error) {
          failures.push(`${label}: ${error.message}`);
          console.error(`FAIL ${label}: ${error.message}`);
        } finally { await context.close(); }
      }

      for (const width of [320, 1024, 1440]) {
        const context = await browser.newContext({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
        const page = await context.newPage();
        try {
          await mockApi(page);
          await page.goto(base);
          await openBooking(page);
          await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
          await assertLayout(page, `${engine} ${width} texte à 200 %`);
          for (const route of ['mentions-legales.php', 'annuler.php']) {
            await page.goto(`${base}/${route}`);
            await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
            await assertLayout(page, `${engine} ${width} ${route} texte à 200 %`);
          }
          console.log(`OK ${engine} texte à 200 % ${width}px`);
        } catch (error) { failures.push(`${engine} zoom ${width}: ${error.message}`); console.error(failures.at(-1)); }
        finally { await context.close(); }
      }

      for (const javaScriptEnabled of [true, false]) {
        const context = await browser.newContext({ viewport: { width: 320, height: 740 }, javaScriptEnabled, reducedMotion: 'reduce' });
        const page = await context.newPage();
        try {
          if (javaScriptEnabled) await page.route('**/js/script.js*', route => route.abort());
          await page.goto(base);
          await assertLayout(page, `${engine} sans script`);
          assert(await page.locator('#nav').isVisible(), 'navigation accessible sans script');
          assert(await page.locator('.booking__fallback').isVisible(), 'alternative à la réservation dynamique');
          assert.equal(await page.locator('.presta__offre:visible').count(), 4, 'toutes les formules lisibles sans script');
          assert.equal(await page.locator('#bookingForm').isVisible(), false);
          console.log(`OK ${engine} ${javaScriptEnabled ? 'script bloqué' : 'JavaScript désactivé'}`);
        } catch (error) { failures.push(`${engine} sans script: ${error.message}`); console.error(failures.at(-1)); }
        finally { await context.close(); }
      }

      const context = await browser.newContext({ viewport: { width: 390, height: 844 }, reducedMotion: 'reduce' });
      const page = await context.newPage();
      try {
        const state = { offline: true };
        await mockApi(page, state);
        await page.goto(base);
        await page.locator('label:has(input[value="tirage"])').click();
        await page.locator('#calendarRetry').waitFor({ state: 'visible' });
        state.offline = false;
        await page.locator('#calendarRetry').click();
        await page.locator('[data-date="2030-01-14"]').waitFor();
        await page.locator('#calendar [data-step="1"]').click();
        await page.locator('[data-date="2030-02-14"]').waitFor();
        await page.locator('#burger').click();
        await page.locator('.header .brand').click();
        assert.equal(await page.locator('#burger').getAttribute('aria-expanded'), 'false');
        await page.locator('#burger').click();
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('#burger').evaluate(el => el === document.activeElement), true);
        await page.locator('#burger').click();
        await page.setViewportSize({ width: 1440, height: 900 });
        await settle(page);
        await page.setViewportSize({ width: 390, height: 844 });
        await settle(page);
        assert.equal(await page.locator('#burger').getAttribute('aria-expanded'), 'false');
        await page.locator('[data-prefill]').click();
        assert((await page.locator('#demandeMessage').inputValue()).length > 0);
        for (const [name, value] of Object.entries({ nom: 'Exemple', prenom: 'Camille', email: 'client@example.test', telephone: '+41790000000' })) await page.locator(`#rdvForm [name="${name}"]`).fill(value);
        await page.locator('#rdvForm [name="consent"]').check();
        await page.locator('#rdvForm .form__submit').click();
        await page.locator('#formStatus.is-ok').waitFor();
        assert.equal(state.contact, 1);
        checks++;
      } catch (error) { failures.push(`${engine} interactions: ${error.message}`); }
      finally { await context.close(); }

      const animatedContext = await browser.newContext({ viewport: { width: 320, height: 740 } });
      const animatedPage = await animatedContext.newPage();
      try {
        await mockApi(animatedPage);
        await animatedPage.goto(base);
        await animatedPage.locator('.booking__online').evaluate(el => {
          el.style.minHeight = '10000px';
          el.scrollIntoView({ block: 'center', behavior: 'instant' });
        });
        await animatedPage.waitForFunction(() => getComputedStyle(document.querySelector('.booking__online')).opacity === '1', null, { timeout: 5000 });
        checks++;
      } catch (error) { failures.push(`${engine} animation d'un long formulaire: ${error.message}`); }
      finally { await animatedContext.close(); }
    } finally { await browser.close(); }
  }
  console.log(`${checks} contrôles réussis ; ${failures.length} échecs.`);
  if (screenshots) fs.writeFileSync(path.join(screenshots, 'checks.json'), JSON.stringify({ checks, failures, engines }, null, 2));
  assert.deepEqual(failures, []);
})().catch(error => { console.error(error); process.exitCode = 1; });
