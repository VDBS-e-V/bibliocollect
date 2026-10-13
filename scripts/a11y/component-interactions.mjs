#!/usr/bin/env node
/**
 * Real-browser regression checks for the opt-in design-system interactions.
 * No form submission, no backend writes and no test records are modified.
 *
 * Run with the existing Laravel server and built Vite assets:
 * BROWSER=/usr/bin/google-chrome npm run check:components
 */
import assert from 'node:assert/strict';
import puppeteer from 'puppeteer-core';
import { base, findBrowser } from '../support/site.mjs';

const browser = await puppeteer.launch({
  executablePath: findBrowser(),
  headless: 'new',
  args: ['--no-sandbox'],
});
const page = await browser.newPage();
const successes = [];

const check = async (name, run) => {
  await run();
  successes.push(name);
  console.log('OK  ' + name);
};

try {
  await page.setViewport({ width: 375, height: 900, deviceScaleFactor: 1 });
  const response = await page.goto(base + '/design-lab/komponenten', {
    waitUntil: 'networkidle0', timeout: 45000,
  });
  assert.equal(response?.status(), 200, 'Designlab must be publicly available');

  await check('Combobox: keyboard selection updates native form value', async () => {
    await page.focus('#bc-k14-combobox-search');
    await page.keyboard.down('Control');
    await page.keyboard.press('a');
    await page.keyboard.up('Control');
    await page.type('#bc-k14-combobox-search', 'Welt');
    await page.waitForFunction(() => document.querySelector('#bc-k14-combobox-search')?.getAttribute('aria-expanded') === 'true');
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('Enter');
    const state = await page.evaluate(() => ({
      value: document.querySelector('#bc-k14-combobox')?.value,
      expanded: document.querySelector('#bc-k14-combobox-search')?.getAttribute('aria-expanded'),
    }));
    assert.equal(state.value, '1');
    assert.equal(state.expanded, 'false');
  });

  await check('Data grid: select all skips disabled rows and updates count', async () => {
    const section = '#bc-core-data-grid';
    await page.click(section + ' [data-ui-grid-select-all]');
    const state = await page.evaluate((selector) => {
      const grid = document.querySelector(selector);
      return {
        checked: [...grid.querySelectorAll('[data-ui-grid-item]')].filter((item) => item.checked).length,
        disabled: [...grid.querySelectorAll('[data-ui-grid-item]')].filter((item) => item.disabled).length,
        counter: grid.querySelector('[data-ui-grid-count]')?.textContent.trim(),
      };
    }, section);
    assert.deepEqual(state, { checked: 2, disabled: 1, counter: '2 ausgewählt' });
  });

  await check('Command palette: real filtering and Escape close', async () => {
    await page.click('[data-ui-dialog-open="bc-k15-palette"]');
    await page.waitForFunction(() => document.querySelector('#bc-k15-palette')?.open === true);
    await page.focus('#bc-k15-palette-search');
    await page.type('#bc-k15-palette-search', 'Berechtigungsmatrix');
    const filtered = await page.evaluate(() => {
      const modal = document.querySelector('#bc-k15-palette');
      return {
        visible: [...modal.querySelectorAll('[data-ui-command-item]')].filter((item) => !item.hidden).length,
        href: [...modal.querySelectorAll('[data-ui-command-item]')].find((item) => !item.hidden)?.querySelector('a')?.getAttribute('href'),
      };
    });
    assert.deepEqual(filtered, { visible: 1, href: '#bc-core-permission-matrix' });
    await page.keyboard.press('Escape');
    await page.waitForFunction(() => document.querySelector('#bc-k15-palette')?.open === false);
  });

  await check('Repeater: add and remove preserve native field names', async () => {
    const root = '#bc-core-repeater-field [data-ui-repeater]';
    const before = await page.$$eval(root + ' [data-ui-repeater-rows] [data-ui-repeater-row]', (nodes) => nodes.length);
    assert.equal(before, 2);
    await page.click(root + ' [data-ui-repeater-add]');
    const added = await page.evaluate((selector) => {
      const root = document.querySelector(selector);
      return {
        count: root.querySelectorAll('[data-ui-repeater-rows] [data-ui-repeater-row]').length,
        name: root.querySelector('[name="bc-k17-contributors[2][name]"]')?.name,
      };
    }, root);
    assert.deepEqual(added, { count: 3, name: 'bc-k17-contributors[2][name]' });
    await page.click(root + ' [data-ui-repeater-rows] [data-ui-repeater-row]:last-child [data-ui-repeater-remove]');
    const after = await page.$$eval(root + ' [data-ui-repeater-rows] [data-ui-repeater-row]', (nodes) => nodes.length);
    assert.equal(after, 2);
  });

  await check('Transfer list: moving items updates selected hidden values', async () => {
    const root = '#bc-core-transfer-list [data-ui-transfer-list]';
    await page.select(root + ' [data-ui-transfer-available]', 'journals');
    await page.click(root + ' [data-ui-transfer-add]');
    const values = await page.$$eval(root + ' [data-ui-transfer-hidden] input', (nodes) => nodes.map((node) => node.value).sort());
    assert.deepEqual(values, ['books', 'journals']);
    const counter = await page.$eval(root + ' [data-ui-transfer-status]', (node) => node.textContent.trim());
    assert.equal(counter, '2 ausgewählt');
  });

  await check('Read-more: preview is visible until full text is opened', async () => {
    const selector = '#bc-core-read-more .bc-read-more';
    const before = await page.$eval(selector, (node) => ({
      open: node.querySelector('details').open,
      excerpt: getComputedStyle(node.querySelector('.bc-read-more__excerpt')).display,
    }));
    assert.equal(before.open, false);
    assert.notEqual(before.excerpt, 'none');
    await page.click(selector + ' summary');
    const after = await page.$eval(selector, (node) => ({
      open: node.querySelector('details').open,
      excerpt: getComputedStyle(node.querySelector('.bc-read-more__excerpt')).display,
    }));
    assert.equal(after.open, true);
    assert.equal(after.excerpt, 'none');
  });

  console.log('\n' + successes.length + ' interactive component checks passed.');
} catch (error) {
  console.error('\nFAIL: ' + error.stack);
  process.exitCode = 1;
} finally {
  await browser.close();
}
