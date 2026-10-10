#!/usr/bin/env node
/**
 * Automatische Barrierefreiheitsprüfung mit axe-core (WCAG 2.2 AA) über die wichtigsten Seiten, hell und dunkel.
 *
 * Aufruf:  npm run a11y            (gegen http://127.0.0.1:8000, mit angemeldeter Verwaltung für die internen Seiten)
 *          BASE_URL=http://127.0.0.1:8010 THEMES=light node scripts/a11y/axe-check.mjs
 *
 * Umgebung:
 *   BASE_URL        Adresse der laufenden Anwendung (Standard http://127.0.0.1:8000)
 *   BROWSER         Pfad zu Chrome oder Edge (sonst werden übliche Orte probiert)
 *   LOGIN_EMAIL     Konto für die geschützten Seiten (Standard: Demo-Verwaltung)
 *   LOGIN_PASSWORD  Passwort dazu (Standard: Demo-Passwort aus dem DemoSeeder)
 *   THEMES          light,dark (Standard beide)
 *   FAIL_ON         kleinste Wirkung, die den Lauf scheitern lässt: minor | moderate | serious | critical (Standard serious)
 *   REPORT          Pfad der JSON-Datei (Standard a11y-report/report.json)
 *
 * Das ersetzt keine Prüfung mit Tastatur und Screenreader (siehe docs/BARRIEREFREIHEIT.md), fängt aber Rückschritte ab.
 */
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import puppeteer from 'puppeteer-core';

const require = createRequire(import.meta.url);
const axeSource = fs.readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');

const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const themes = (process.env.THEMES || 'light,dark').split(',').map((t) => t.trim()).filter(Boolean);
const levels = ['minor', 'moderate', 'serious', 'critical'];
const failOn = levels.indexOf(process.env.FAIL_ON || 'serious');
const reportPath = process.env.REPORT || 'a11y-report/report.json';
const email = process.env.LOGIN_EMAIL || 'management@demo.bibliocollect.test';
const password = process.env.LOGIN_PASSWORD || 'Bibliothek2026!';

const guestPages = [
  ['Startseite', '/'],
  ['Katalog', '/katalog'],
  ['Katalog mit Suche', '/katalog?q=die'],
  ['Erweiterte Suche', '/katalog/erweiterte-suche'],
  ['Buchwunsch', '/buchwunsch'],
  ['Impressum', '/impressum'],
  ['Datenschutz', '/datenschutz'],
  ['Barrierefreiheit', '/barrierefreiheit'],
  ['Anmelden', '/anmelden'],
];

const memberPages = [
  ['Konto', '/konto'],
  ['Merkliste', '/konto/merkliste'],
  ['Leselisten', '/konto/leselisten'],
  ['Buchwünsche (Konto)', '/konto/buchwuensche'],
  ['Betrieb', '/betrieb'],
  ['Ausleihe', '/betrieb/ausleihe'],
  ['Einsortieren', '/betrieb/einsortieren'],
  ['Hilfe', '/betrieb/hilfe'],
  ['Vormerkungen', '/betrieb/vormerkungen'],
  ['Katalog verwalten', '/betrieb/katalog'],
  ['Medium erfassen', '/betrieb/katalog/erfassen'],
  ['Katalogqualität', '/betrieb/katalog/qualitaet'],
  ['Etiketten Vorrat', '/betrieb/etiketten/vorrat'],
  ['Ausweise', '/betrieb/ausweise'],
  ['Verwaltung', '/verwaltung'],
  ['Regalbrett-Etiketten', '/verwaltung/regalbretter/etiketten'],
  ['Systemzustand', '/verwaltung/systemzustand'],
  ['Benutzer', '/verwaltung/benutzer'],
  ['Update', '/verwaltung/update'],
  ['Ausleihkonten', '/betrieb/ausleihkonten'],
  ['Ausleihkonto anlegen', '/betrieb/ausleihkonten/neu'],
  ['Ausweise ausgeben', '/betrieb/ausweise-ausgabe'],
  ['Ausweis-Motive', '/betrieb/ausweise/motive'],
  ['Aussondern', '/betrieb/aussondern'],
  ['Buchwünsche (Betrieb)', '/betrieb/buchwuensche'],
  ['Inventur', '/betrieb/inventur'],
  ['Überfällig', '/betrieb/ueberfaellig'],
  ['Vorgänge', '/betrieb/vorgaenge'],
  ['Klassenlisten', '/betrieb/klassenlisten'],
  ['Statistik', '/betrieb/statistik'],
  ['Katalogimport', '/betrieb/katalog/import'],
  ['Regalbretter', '/verwaltung/regalbretter'],
  ['Themenbereiche', '/verwaltung/themenbereiche'],
  ['Schule', '/verwaltung/schule'],
  ['Regeln', '/verwaltung/regeln'],
  ['Öffnungszeiten', '/verwaltung/oeffnungszeiten'],
  ['Seiten', '/verwaltung/seiten'],
  ['Protokoll', '/verwaltung/protokoll'],
];

function findBrowser() {
  const candidates = [
    process.env.BROWSER,
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  ].filter(Boolean);
  const found = candidates.find((candidate) => fs.existsSync(candidate));

  if (!found) {
    console.error('Kein Chrome oder Edge gefunden. Pfad in BROWSER angeben.');
    process.exit(2);
  }

  return found;
}

const browser = await puppeteer.launch({ executablePath: findBrowser(), headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setViewport({ width: 1200, height: 900 });

let currentTheme = 'light';

async function load(url) {
  const response = await page.goto(url.startsWith('http') ? url : base + url, { waitUntil: 'networkidle0', timeout: 45000 });

  // Das Farbschema steuert die Seite über ein Attribut am Wurzelelement (wie der Umschalter in der Kopfzeile).
  await page.evaluate((theme) => {
    if (theme === 'dark') {
      document.documentElement.setAttribute('data-vdbs-theme', 'dark');
    } else {
      document.documentElement.removeAttribute('data-vdbs-theme');
    }
  }, currentTheme);

  // Übergänge (Hintergrundfarbe) abwarten, sonst misst axe halb umgeschaltete Farben.
  await new Promise((resolve) => setTimeout(resolve, 600));

  return response ? response.status() : 0;
}

async function run(pages, theme, results) {
  currentTheme = theme;

  for (const [name, url] of pages) {
    let status;

    try {
      status = await load(url);
    } catch (error) {
      results.push({ page: name, url, theme, error: error.message, violations: [] });
      continue;
    }

    if (status >= 400) {
      results.push({ page: name, url, theme, error: `HTTP ${status}`, violations: [] });
      continue;
    }

    await page.evaluate(axeSource);
    const outcome = await page.evaluate(async () => {
      // eslint-disable-next-line no-undef
      const result = await axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] } });

      return result.violations.map((v) => ({
        id: v.id,
        impact: v.impact,
        help: v.help,
        helpUrl: v.helpUrl,
        nodes: v.nodes.slice(0, 5).map((n) => ({ target: n.target.join(' '), html: (n.html || '').slice(0, 200), summary: (n.failureSummary || '').split('\n').slice(1, 3).join(' ').trim() })),
        count: v.nodes.length,
      }));
    });

    results.push({ page: name, url, theme, violations: outcome });
  }
}

const results = [];

try {
  for (const theme of themes) {
    await page.deleteCookie(...(await page.cookies()));
    await run(guestPages, theme, results);

    // Detailseite und Regalseite: erste Fundstelle aus dem Katalog
    currentTheme = theme;
    await load('/katalog');
    const titleHref = await page.$eval('a[href*="/katalog/titel/"]', (a) => new URL(a.href).pathname).catch(() => null);

    if (titleHref) {
      await run([['Titelseite', titleHref]], theme, results);
    }

    await load('/anmelden');
    await page.type('input[name=email]', email);
    await page.type('input[name=password]', password);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);

    if (page.url().includes('/anmelden')) {
      console.error('Anmeldung fehlgeschlagen (LOGIN_EMAIL / LOGIN_PASSWORD prüfen).');
      process.exit(2);
    }

    await run(memberPages, theme, results);
  }
} finally {
  await browser.close();
}

fs.mkdirSync(path.dirname(reportPath), { recursive: true });
fs.writeFileSync(reportPath, JSON.stringify(results, null, 2));

let failures = 0;
let errors = 0;

for (const result of results) {
  const label = `${result.theme === 'dark' ? 'dunkel' : 'hell  '} ${result.page} (${result.url})`;

  if (result.error) {
    errors++;
    console.log(`FEHLER   ${label}: ${result.error}`);
    continue;
  }

  const relevant = result.violations.filter((v) => levels.indexOf(v.impact) >= failOn);
  const minor = result.violations.length - relevant.length;
  console.log(`${relevant.length ? 'VERSTOSS ' : 'ok       '}${label}${minor ? `  (+${minor} leichtere Hinweise)` : ''}`);

  for (const violation of relevant) {
    failures++;
    console.log(`   [${violation.impact}] ${violation.id}: ${violation.help} (${violation.count}x)`);

    for (const node of violation.nodes.slice(0, 3)) {
      console.log(`      ${node.target}${node.summary ? ' – ' + node.summary : ''}`);

      if (node.html) {
        console.log(`        ${node.html}`);
      }
    }
  }
}

console.log(`\n${results.length} Prüfungen, ${failures} Verstöße ab Stufe „${levels[failOn]}“, ${errors} Ladefehler. Bericht: ${reportPath}`);
process.exit(failures || errors ? 1 : 0);
