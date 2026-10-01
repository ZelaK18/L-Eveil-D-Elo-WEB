// PLAYWRIGHT_MODULE : chemin d'une installation de playwright si elle est externe au projet.
const playwright = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

function launchBrowser(engine = process.env.BROWSER_ENGINE || 'chromium') {
  return playwright[engine].launch({
    headless: true,
    ...(engine === 'chromium' && process.platform === 'win32' ? { channel: 'msedge' } : {}),
  });
}

function availability(month = '2030-01') {
  return {
    ok: true, month, min: '2030-01', max: '2030-03',
    services: Object.fromEntries(['tirage', 'pendule', 'coaching.0', 'coaching.1', 'coaching.2', 'coaching.3']
      .map(key => [key, { [`${month}-14`]: ['09:00', '10:00'] }])),
  };
}

module.exports = { launchBrowser, availability };
