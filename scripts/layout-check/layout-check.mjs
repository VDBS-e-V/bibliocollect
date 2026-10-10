#!/usr/bin/env node
/**
 * Layout-Prüfung für Tablet und Handy: öffnet die wichtigen Seiten bei mehreren Breiten und meldet
 *   - seitliches Scrollen der Seite (Fehler),
 *   - einzelne Elemente, die über den Rand ragen, ohne in einem eigenen Scrollbereich zu liegen (Fehler),
 *   - zu kleine Tippflächen unter 24 × 24 px (Warnung; mit STRICT=1 ein Fehler),
 *   - abgeschnittene Texte in Elementen mit "overflow: hidden" ohne Auslassungszeichen (Warnung).
 *
 * Aufruf:  npm run check:layout
 *          WIDTHS=320,768 PAGES=Katalog,Konto SHOTS=1 BASE_URL=http://127.0.0.1:8010 npm run check:layout
 *
 * Umgebung:
 *   BASE_URL  Adresse der laufenden Anwendung (Standard http://127.0.0.1:8000)
 *   BROWSER   Pfad zu Chrome oder Edge
 *   WIDTHS    Breiten in Pixel (Standard 320,375,768,1024)
 *   PAGES     nur Seiten, deren Name einen dieser Texte enthält (kommagetrennt)
 *   STRICT    1 = auch Warnungen lassen den Lauf scheitern
 *   SHOTS     1 = Bildschirmfotos aller Seiten, sonst nur von Seiten mit Fehlern
 *   OUT       Ordner für Bericht und Bilder (Standard layout-report)
 */
import fs from 'node:fs';
import path from 'node:path';
import puppeteer from 'puppeteer-core';
import { base, findBrowser, guestPages, login, memberPages } from '../support/site.mjs';

const widths = (process.env.WIDTHS || '320,375,768,1024').split(',').map((w) => Number(w.trim())).filter(Boolean);
const only = (process.env.PAGES || '').split(',').map((t) => t.trim().toLowerCase()).filter(Boolean);
const strict = process.env.STRICT === '1';
const allShots = process.env.SHOTS === '1';
const out = process.env.OUT || 'layout-report';

fs.mkdirSync(out, { recursive: true });

const selected = (pages) => pages.filter(([name]) => only.length === 0 || only.some((t) => name.toLowerCase().includes(t)));

const browser = await puppeteer.launch({ executablePath: findBrowser(), headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();

/** Läuft im Browser: sammelt die Befunde der geladenen Seite. */
function inspect() {
  const doc = document.documentElement;
  const viewport = doc.clientWidth;
  const label = (el) => {
    const cls = typeof el.className === 'string' && el.className.trim() ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.') : '';

    return el.tagName.toLowerCase() + cls;
  };

  const inScrollArea = (el) => {
    for (let parent = el.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
      const style = getComputedStyle(parent);

      if (/(auto|scroll|hidden|clip)/.test(style.overflowX) && parent.getBoundingClientRect().right <= viewport + 1) {
        return true;
      }
    }

    return false;
  };

  const overflow = [];
  const small = [];
  const clipped = [];

  for (const el of document.querySelectorAll('body *')) {
    const rect = el.getBoundingClientRect();
    const style = getComputedStyle(el);

    if (rect.width === 0 || rect.height === 0 || style.visibility === 'hidden' || style.display === 'none') {
      continue;
    }

    if ((rect.right > viewport + 1 || rect.left < -1) && style.position !== 'fixed' && !el.closest('[hidden]') && !inScrollArea(el)) {
      overflow.push(`${label(el)} ${Math.round(rect.left)}..${Math.round(rect.right)}`);
    }

    if (el.matches('a[href], button, select, summary, input:not([type=hidden]):not([type=checkbox]):not([type=radio])') && (rect.width < 24 || rect.height < 24)) {
      // Links im Fließtext sind nach WCAG 2.5.8 ausgenommen.
      const inline = el.matches('a') && style.display === 'inline' && el.closest('p, li, dd, td, small, label');

      if (!inline && !el.closest('.bc-visually-hidden')) {
        small.push(`${label(el)} "${(el.textContent || el.getAttribute('aria-label') || '').trim().slice(0, 24)}" ${Math.round(rect.width)}×${Math.round(rect.height)}`);
      }
    }

    if (!el.matches('.sr-only, .bc-visually-hidden, [class*="visually-hidden"]') && /(hidden|clip)/.test(style.overflowX) && style.textOverflow !== 'ellipsis' && el.scrollWidth > el.clientWidth + 2 && el.children.length === 0 && (el.textContent || '').trim() !== '') {
      clipped.push(`${label(el)} "${(el.textContent || '').trim().slice(0, 30)}"`);
    }
  }

  return { scrollWidth: doc.scrollWidth, viewport, overflow: overflow.slice(0, 10), small: small.slice(0, 10), clipped: clipped.slice(0, 6) };
}

const results = [];
let errors = 0;
let warnings = 0;

async function check(pages, width, requiresLogin) {
  for (const [name, url] of selected(pages)) {
    await page.setViewport({ width, height: 900, deviceScaleFactor: 1 });
    const entry = { page: name, url, width, errors: [], warnings: [] };

    try {
      const response = await page.goto(url.startsWith('http') ? url : base + url, { waitUntil: 'networkidle0', timeout: 45000 });

      if (!response || response.status() >= 400) {
        entry.errors.push(`HTTP ${response ? response.status() : 0}`);
      } else {
        const info = await page.evaluate(inspect);

        if (info.scrollWidth > info.viewport + 1) {
          entry.errors.push(`Seite scrollt seitlich (${info.scrollWidth} px bei ${info.viewport} px)`);
        }

        entry.errors.push(...info.overflow.map((text) => `ragt über den Rand: ${text}`));
        entry.warnings.push(...info.small.map((text) => `kleine Tippfläche: ${text}`), ...info.clipped.map((text) => `abgeschnittener Text: ${text}`));
      }
    } catch (error) {
      entry.errors.push(`Ladefehler: ${error.message}`);
    }

    if (entry.errors.length || (strict && entry.warnings.length) || allShots) {
      const file = `${String(width)}-${name.replace(/[^A-Za-z0-9äöüÄÖÜß]+/g, '-')}.png`;
      entry.screenshot = file;
      await page.screenshot({ path: path.join(out, file), fullPage: true }).catch(() => {});
    }

    errors += entry.errors.length;
    warnings += entry.warnings.length;
    results.push(entry);

    const state = entry.errors.length ? 'FEHLER  ' : entry.warnings.length ? 'Warnung ' : 'ok      ';
    console.log(`${state}${String(width).padStart(4)} px  ${name} (${url})`);

    for (const text of entry.errors) {
      console.log(`    ✗ ${text}`);
    }

    for (const text of entry.warnings.slice(0, 4)) {
      console.log(`    ! ${text}`);
    }

    if (entry.warnings.length > 4) {
      console.log(`    ! … und ${entry.warnings.length - 4} weitere`);
    }
  }

  return requiresLogin;
}

try {
  for (const width of widths) {
    await page.deleteCookie(...(await page.cookies()));
    await check(guestPages, width, false);
    await login(page);
    await check(memberPages, width, true);
  }
} finally {
  await browser.close();
}

fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(results, null, 2));
console.log(`\n${results.length} Prüfungen bei ${widths.join(', ')} px: ${errors} Fehler, ${warnings} Warnungen. Bericht: ${path.join(out, 'report.json')}`);
process.exit(errors || (strict && warnings) ? 1 : 0);
