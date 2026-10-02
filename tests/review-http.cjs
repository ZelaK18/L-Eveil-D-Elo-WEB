// Contrôle le virtual host Laragon réel ; aucun formulaire n'est envoyé.
// REVIEW_HOST/REVIEW_PORT/REVIEW_ADDRESS permettent de choisir le serveur local.
const http = require('node:http');
const host = process.env.REVIEW_HOST || 'LeveilDelo.app';
const tests = [
  ['/', 200], ['/mentions-legales', 200], ['/confidentialite', 200],
  ['/site/', 200], ['/site/mentions-legales', 200], ['/site/confidentialite', 200],
  ['/site/annuler', 200], ['/site/bientot', 200], ['/site/sitemap.xml', 200],
  ['/site/robots.txt', 200], ['/site/css/theme.css?v=review', 200],
  ['/site/js/script.js?v=review', 200],
  ['/index.php', 301], ['/site/index.php', 301],
  ['/site/annuler.php?r=test&s=faux', 308],
  ['/app/config.php', 403], ['/site/app/config.php', 403], ['/site/storage/', 403],
  ['/site/textes/site-text.php', 403], ['/php/php.ini', 403], ['/.git/config', 403],
  ['/site/css/charte-graphique.txt', 403], ['/tests/package.json', 403],
  ['/site/api/contact.php', 405], ['/site/api/booking.php', 405],
];
function get(url) {
  return new Promise((resolve, reject) => {
    const req = http.get({ host: process.env.REVIEW_ADDRESS || '127.0.0.1', port: process.env.REVIEW_PORT || 80, path: url, headers: { Host: host, Accept: 'application/json' }, timeout: 5000 }, res => {
      let body = '';
      res.setEncoding('utf8');
      res.on('data', chunk => { if (res.statusCode === 200 && /robots|sitemap/.test(url)) body += chunk; });
      res.on('end', () => resolve({ status: res.statusCode, headers: res.headers, body }));
    });
    req.on('timeout', () => req.destroy(new Error('Délai dépassé')));
    req.on('error', reject);
  });
}
(async () => {
  const failures = [];
  for (const [url, expected] of tests) {
    const result = await get(url);
    if (result.status !== expected) failures.push(`${url}: HTTP ${result.status}, attendu ${expected}`);
    if (url.includes('annuler.php') && !result.headers.location?.endsWith('/site/annuler?r=test&s=faux')) failures.push('Paramètres perdus à la redirection d’annulation');
    if (url.endsWith('/robots.txt') && !result.body.includes('Disallow: /site/api/')) failures.push('Préfixe du dossier absent de robots.txt');
    if (/\.(css|js)\?/.test(url) && !result.headers['cache-control']?.includes('max-age=')) failures.push(`Cache manquant : ${url}`);
    if (url.endsWith('sitemap.xml') && !result.body.includes('/site/confidentialite</loc>')) failures.push('Page confidentialité absente du sitemap');
  }
  console.log(JSON.stringify({ host, checks: tests.length, failures }, null, 2));
  process.exitCode = failures.length ? 1 : 0;
})().catch(error => { console.error(error.message); process.exitCode = 1; });
