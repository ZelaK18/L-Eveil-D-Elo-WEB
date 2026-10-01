// PLAYWRIGHT_MODULE peut pointer vers une installation externe de playwright-core.
// Démarrer le site local, puis : node tests/booking-browser.cjs [http://127.0.0.1:8765]
// Toutes les API sont interceptées : aucun agenda, e-mail ou fichier client réel n'est modifié.
const assert = require('node:assert/strict');
const { launchBrowser, availability } = require('./browser-helpers.cjs');

(async () => {
  const browser = await launchBrowser();
  try {
    for (const width of [1440, 768, 390, 320]) {
      const page = await browser.newPage({ viewport: { width, height: 1000 }, reducedMotion: 'reduce' });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      let submitted = [];
      let failSlot = false;
      await page.route('**/api/**', async route => {
        if (route.request().url().includes('/availability.php')) {
          return route.fulfill({ json: { ...availability(), max: '2030-01' } });
        }
        assert(route.request().url().includes('/booking.php'), 'API inattendue bloquée');
        submitted.push(route.request().postData());
        if (failSlot) {
          failSlot = false;
          return route.fulfill({ status: 409, json: { ok: false, message: 'Ce créneau vient d’être pris. Choisissez-en un autre.', code: 'slot_taken' } });
        }
        return route.fulfill({ json: { ok: true, service: 'Séance de test', when: 'lundi 14 janvier 2030 à 10h00', visio: false, email_sent: true } });
      });
      await page.goto(process.argv[2] || 'http://127.0.0.1:8765', { waitUntil: 'domcontentloaded' });
      const originalUrl = page.url();
      const booking = page.locator('#bookingForm');
      const chooseSlot = async () => {
        await page.locator('[data-date="2030-01-14"]').click();
        await page.locator('.slot[data-time="10:00"]').click();
        await page.locator('#bookingDetails').waitFor({ state: 'visible' });
      };
      const fill = async () => {
        for (const [name, value] of Object.entries({ nom: 'Exemple', prenom: 'Camille', email: 'client@example.test', telephone: '+41790000000', naissance: '1990-03-15' })) {
          await booking.locator(`[name="${name}"]`).fill(value);
        }
        for (const select of await booking.locator('[data-intake]:not([hidden]) select').all()) await select.selectOption({ index: 1 });
        for (const textarea of await booking.locator('[data-intake]:not([hidden]) textarea').all()) await textarea.fill('Une réponse personnelle de test.');
        for (const checkbox of await booking.locator('.consent input').all()) await checkbox.check();
      };
      for (const service of ['tirage', 'pendule', 'coaching.2']) {
        if (service.startsWith('coaching')) await booking.locator('.choices__groupe').click();
        await booking.locator(`label:has(input[name="service"][value="${service}"])`).click();
        await chooseSlot();
        assert.equal(await page.locator('[data-intake]:visible').count(), 1);
        assert.equal(await page.locator('[data-intake]:visible').getAttribute('data-intake'), service.split('.')[0]);
        const before = submitted.length;
        await booking.locator('.form__submit').click();
        assert.equal(submitted.length, before, 'formulaire vide : aucune soumission');
        await fill();
        await page.locator('.slot[data-time="09:00"]').click();
        assert.equal(await booking.locator('.consent input:checked').count(), 0, 'autre horaire : accords à redonner');
        await fill();
        if (service === 'pendule') failSlot = true;
        await booking.locator('.form__submit').click();
        if (service === 'pendule') {
          await page.locator('#bookingStatus.is-error').waitFor();
          await chooseSlot();
          assert.equal(await booking.locator('[name="nom"]').inputValue(), 'Exemple', 'réponses conservées après conflit');
          assert.equal(await booking.locator('.consent input:checked').count(), 0);
          await fill();
          await booking.locator('.form__submit').click();
        }
        await page.locator('#bookingDone').waitFor({ state: 'visible' });
        assert.equal(page.url(), originalUrl, 'aucun rechargement de page');
        const posted = submitted.at(-1);
        if (service === 'coaching.2') assert(!posted.includes('questionnaire[tirage]'), 'champs cachés non envoyés');
        else assert(!posted.includes('questionnaire[objectif]'), 'champs coaching non envoyés');
        await page.locator('#bookingAgain').click();
        assert.equal(await booking.locator('[name="nom"]').inputValue(), '', 'nouvelle réservation vierge');
      }
      await booking.locator('.choices__groupe').click();
      await booking.locator('label:has(input[value="coaching.1"])').click();
      await chooseSlot();
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true, 'aucun débordement horizontal');
      assert.deepEqual(errors, []);
      if (process.env.BOOKING_SCREENSHOT_DIR) {
        await page.locator('.booking__online').screenshot({ path: `${process.env.BOOKING_SCREENSHOT_DIR}/booking-${width}.png` });
        await page.locator('#bookingIntakeTitle').scrollIntoViewIfNeeded();
        await page.screenshot({ path: `${process.env.BOOKING_SCREENSHOT_DIR}/booking-viewport-${width}.png` });
      }
      console.log(`OK navigateur ${width}px : formulaires, validations, conflit, accords, confirmation et mobile`);
      await page.close();
    }
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
