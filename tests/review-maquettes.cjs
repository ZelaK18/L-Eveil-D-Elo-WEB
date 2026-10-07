// Aperçus des huit compositions et contrôles de lecture, sans appel de réservation.
// Usage : node tests/review-maquettes.cjs [http://127.0.0.1:8097]
const { chromium } = require('playwright-core');
const axe = require('axe-core');
const fs = require('node:fs');
const path = require('node:path');
const base = process.argv[2] || 'http://127.0.0.1:8097';
const root = path.resolve(__dirname, '..');
const failures = [];
(async () => {
  const browser = await chromium.launch({ channel: 'chrome', headless: true });
  const context = await browser.newContext({ reducedMotion: 'reduce' });
  await context.route('**/api/**', route => route.fulfill({ json: { ok: false, message: 'Aperçu local' } }));
  const page = await context.newPage();
  page.on('pageerror', error => failures.push(error.message));
  page.on('response', response => { if (response.status() >= 400) failures.push(`HTTP ${response.status()} ${response.url()}`); });
  try {
    for (const v of [1, 2, 3, 4, 5, 6, 7, 8, 0]) {
      await page.setViewportSize({ width: 1440, height: 1050 });
      await page.goto(`${base}/site/maquettes.php${v ? '?v=' + v : ''}`);
      await page.evaluate(() => document.fonts.ready);
      await page.locator('img').evaluateAll(images => Promise.all(images.map(img => { img.loading = 'eager'; return img.decode(); })));
      if (v) {
        await page.screenshot({ path: path.join(root, `site/images/maquette-${v}.png`) });
        if (v === 8) await page.locator('#tab-coaching').click();
        await page.locator('#prestations').evaluate(el => window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 20));
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        await page.screenshot({ path: path.join(root, `site/images/maquette-${v}-prestations.png`) });
      }
      else await page.screenshot({ path: path.join(root, 'tests/maquettes-galerie.png'), fullPage: true });
      await page.addScriptTag({ content: axe.source });
      const issues = await page.evaluate(async () => (await axe.run(document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa'] }
      })).violations.map(v => ({ rule: v.id, nodes: v.nodes.map(n => ({ target: n.target, reason: n.failureSummary })) })));
      if (issues.length) failures.push({ variant: v, issues });
      if (v) {
        if (await page.locator('.service').count() !== 4) failures.push(`v${v}: nombre de prestations`);
        if (await page.locator('.offers details').count() !== 4) failures.push(`v${v}: nombre de formules`);
        await page.locator('.offers summary').first().click();
        if (await page.locator('.offers details').first().getAttribute('open') === null) failures.push(`v${v}: ouverture formule`);
        await page.locator('.offers summary').first().click();
        if (v === 6) {
          for (const accordion of await page.locator('.service-accordion').all()) {
            await accordion.locator(':scope > summary').click();
            await accordion.locator(':scope > summary').click();
          }
        }
        if (v === 8) {
          const tabs = page.getByRole('tab');
          await tabs.first().focus();
          await page.keyboard.press('End');
          if (await tabs.last().getAttribute('aria-selected') !== 'true') failures.push('v8: touche End');
          await page.keyboard.press('ArrowRight');
          if (await tabs.first().getAttribute('aria-selected') !== 'true') failures.push('v8: boucle clavier');
          await page.locator('#tab-coaching').click();
        }
      } else {
        await page.locator('[data-preview-view=services]').click();
        await page.locator('.direction__preview img').evaluateAll(images => Promise.all(images.map(img => { img.loading = 'eager'; return img.decode(); })));
        if (await page.locator('.direction__preview img[src*="-prestations.png"]').count() !== 8) failures.push('Galerie: huit aperçus de prestations');
        await page.locator('[data-preview-view=hero]').click();
        await page.locator('.direction__preview img').evaluateAll(images => Promise.all(images.map(img => { img.loading = 'eager'; return img.decode(); })));
      }
      for (const width of [320, 390, 680, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
        const layout = await page.evaluate(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth, images: [...document.images].filter(i => !i.complete || !i.naturalWidth).map(i => i.src) }));
        if (layout.scroll > width + 1 || layout.images.length) failures.push({ variant: v, layout });
        if (width === 390 && v) await page.screenshot({ path: path.join(root, `tests/maquette-${v}-mobile.png`), fullPage: true });
      }
    }
    const plain = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 900 } });
    const plainPage = await plain.newPage();
    await plainPage.goto(`${base}/site/maquettes.php?v=8`);
    if (await plainPage.locator('.service:visible').count() !== 4) failures.push('v8: prestations accessibles sans JavaScript');
    await plain.close();
    console.log(JSON.stringify({ pages: 9, widths: [320, 390, 680, 768, 1024, 1440], failures }, null, 2));
    fs.writeFileSync(path.join(root, 'tests/maquettes-results.json'), JSON.stringify({ failures }, null, 2));
    process.exitCode = failures.length ? 1 : 0;
  } finally { await browser.close(); }
})();
