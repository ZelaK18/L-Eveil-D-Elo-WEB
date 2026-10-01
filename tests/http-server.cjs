// Serveur reel, requetes publiques et HEAD sur les chemins proteges : aucun formulaire envoye.
// node tests/http-server.cjs [http://127.0.0.1] [leveildelo.app]
const assert = require('node:assert/strict');
const http = require('node:http');
const https = require('node:https');
const base = new URL(process.argv[2] || 'http://127.0.0.1');
const host = process.argv[3] || 'leveildelo.app';

function request(path, { method = 'GET', headers = {} } = {}) {
  return new Promise((resolve, reject) => {
    const url = new URL(path, base);
    const req = (url.protocol === 'https:' ? https : http).request(url, { method, headers: { Host: host, ...headers }, timeout: 10000 }, response => {
      let body = '';
      response.setEncoding('utf8');
      response.on('data', chunk => { if (method !== 'HEAD') body += chunk; });
      response.on('end', () => resolve({ status: response.statusCode, headers: response.headers, body }));
    });
    req.on('timeout', () => req.destroy(new Error('HTTP timeout')));
    req.on('error', reject);
    req.end();
  });
}

(async () => {
  let checks = 0;
  for (const path of ['/', '/mentions-legales.php', '/annuler.php', '/robots.txt', '/sitemap.xml', '/css/style.css', '/js/script.js', '/images/favicon.png', '/images/logo-embleme.png', '/images/test-pp.jpg', '/images/og-image.jpg']) {
    const result = await request(path, { method: 'HEAD' });
    assert.equal(result.status, 200, path);
    checks++;
  }
  for (const path of ['/app/', '/app/secrets.php', '/bin/google-connect.php', '/storage/', '/storage/google-token.php', '/STORAGE/google-token.php', '/tests/booking.php', '/textes/site-text.php', '/php/php.exe', '/.git/HEAD', '/.git/config', '/.env', '/css/charte-graphique.txt', '/CSS/CHARTE-GRAPHIQUE.TXT']) {
    const result = await request(path, { method: 'HEAD' });
    assert([403, 404].includes(result.status), `${path}: HTTP ${result.status}, acces non bloque`);
    checks++;
  }
  for (const [path, destination] of [['/index.php?demande=ok', '/?demande=ok'], ['/index.html', '/'], ['/mentions-legales.html', '/mentions-legales.php']]) {
    const result = await request(path);
    assert.equal(result.status, 301, path);
    const location = new URL(result.headers.location, base);
    assert.equal(location.pathname + location.search, destination);
    checks++;
  }
  assert.equal((await request('/page-inexistante-checkup')).status, 404);
  checks++;
  for (const path of ['/api/contact.php', '/api/booking.php']) {
    const result = await request(path, { headers: { Accept: 'application/json' } });
    assert.equal(result.status, 405);
    assert.equal(JSON.parse(result.body).ok, false);
    checks++;
  }
  const home = await request('/');
  assert.equal(home.headers['x-content-type-options'], 'nosniff');
  assert.equal(home.headers['referrer-policy'], 'no-referrer');
  assert.equal(home.headers['x-frame-options'], 'SAMEORIGIN');
  const domain = home.body.match(/<link rel="canonical" href="([^"]+)"/)[1];
  const robots = await request('/robots.txt');
  const sitemap = await request('/sitemap.xml');
  assert(robots.headers['content-type'].includes('text/plain'));
  assert(robots.body.includes(`Sitemap: ${domain}sitemap.xml`));
  assert(sitemap.headers['content-type'].includes('application/xml'));
  assert(sitemap.body.includes(`<loc>${domain}</loc>`));
  assert.equal((sitemap.body.match(/<lastmod>\d{4}-\d{2}-\d{2}<\/lastmod>/g) || []).length, 2);
  checks += 3;
  const withoutMail = await request('/?demande=ok-sans-email');
  const contactStatus = withoutMail.body.match(/id="formStatus"[^>]*>([\s\S]*?)<\/p>/)[1];
  assert(contactStatus.includes('l’e-mail de confirmation n’a pas pu vous être envoyé'));
  const css = await request('/css/style.css?v=checkup', { method: 'HEAD', headers: { 'Accept-Encoding': 'gzip' } });
  assert(css.headers['cache-control'].includes('max-age='));
  assert.equal(css.headers['content-encoding'], 'gzip');
  checks += 2;
  console.log(`${checks} controles HTTP reussis : pages, fichiers prives, redirections, API, SEO et cache.`);
})().catch(error => { console.error(error); process.exitCode = 1; });
