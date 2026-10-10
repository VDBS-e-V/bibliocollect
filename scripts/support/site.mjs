/**
 * Gemeinsame Bausteine der Browser-Prüfungen (Barrierefreiheit, Layout): Adresse, Browser, Seitenlisten, Anmeldung.
 * Neue Seiten gehören in diese Listen, dann prüfen beide Skripte sie.
 */
import fs from 'node:fs';

export const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
export const email = process.env.LOGIN_EMAIL || 'management@demo.bibliocollect.test';
export const password = process.env.LOGIN_PASSWORD || 'Bibliothek2026!';

export const guestPages = [
  ['Startseite', '/'],
  ['Katalog', '/katalog'],
  ['Katalog mit Suche', '/katalog?q=die'],
  ['Erweiterte Suche', '/katalog/erweiterte-suche'],
  ['Buchwunsch', '/buchwunsch'],
  ['Impressum', '/impressum'],
  ['Datenschutz', '/datenschutz'],
  ['Barrierefreiheit', '/barrierefreiheit'],
  ['Anmelden', '/anmelden'],
  ['Reihen', '/reihen'],
];

export const memberPages = [
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
  ['Reihen prüfen', '/betrieb/katalog/reihen'],
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

export function findBrowser() {
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

/** Meldet die Verwaltung an (sieht alle Bereiche). Beendet das Programm, wenn die Anmeldung scheitert. */
export async function login(page) {
  await page.goto(base + '/anmelden', { waitUntil: 'networkidle0' });
  await page.type('input[name=email]', email);
  await page.type('input[name=password]', password);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);

  if (page.url().includes('/anmelden')) {
    console.error('Anmeldung fehlgeschlagen (LOGIN_EMAIL / LOGIN_PASSWORD prüfen).');
    process.exit(2);
  }
}
