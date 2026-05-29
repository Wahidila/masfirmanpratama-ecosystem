// Re-shoot desktop only with explicit wait + login verification per route.
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:3001';
const EMAIL = 'admin@masfirmanpratama.com';
const PASSWORD = 'admin123';
const OUT = '../M2-hardening/screenshots';
const VP = { name: 'desktop-1440', width: 1440, height: 900 };

const ROUTES = [
  { id: 'login',  url: '/admin/login',  preLogin: true },
  { id: 'dashboard', url: '/admin/dashboard' },
  { id: 'products-index',  url: '/admin/products' },
  { id: 'products-create', url: '/admin/products/create' },
  { id: 'products-create-validation', url: '/admin/products/create', action: 'submitEmpty' },
  { id: 'orders-index', url: '/admin/orders' },
  { id: 'orders-show',  url: 'FIRST_ORDER' },
  { id: 'settings', url: '/admin/settings' },
  { id: 'wa-notifications', url: '/admin/wa-notifications' },
  { id: 'installment-schemes', url: '/admin/installment-schemes' },
  { id: 'installment-schemes-create', url: '/admin/installment-schemes/create' },
];

(async () => {
  const browser = await chromium.launch({ headless: true });
  const ctx = await browser.newContext({
    viewport: { width: VP.width, height: VP.height },
    deviceScaleFactor: 2,
  });
  const page = await ctx.newPage();

  // Login route capture (pre-auth)
  await page.goto(BASE + '/admin/login', { waitUntil: 'load', timeout: 30_000 });
  await page.waitForSelector('input[name=email]', { timeout: 10_000 });
  await page.screenshot({ path: path.join(OUT, 'login__desktop-1440.png'), fullPage: true });
  console.log('shot: login');

  // Login
  await page.fill('input[name=email]', EMAIL);
  await page.fill('input[name=password]', PASSWORD);
  await Promise.all([
    page.waitForURL(/\/admin\/dashboard/, { timeout: 30_000 }),
    page.click('button[type=submit]'),
  ]);
  await page.waitForLoadState('load');
  // verify auth by checking for logout form presence
  const isAuthed = await page.evaluate(() => !!document.querySelector('form[action*="/admin/logout"]'));
  if (!isAuthed) { console.error('LOGIN FAILED'); process.exit(2); }
  console.log('login OK');

  // Find first order id
  await page.goto(BASE + '/admin/orders', { waitUntil: 'load', timeout: 30_000 });
  const orderHref = await page.evaluate(() => {
    const a = document.querySelector('a[href*="/admin/orders/"]:not([href$="/admin/orders"])');
    return a?.getAttribute('href') ?? null;
  });
  console.log('first order:', orderHref);

  for (const route of ROUTES.slice(1)) {
    const url = route.url === 'FIRST_ORDER'
      ? (orderHref ? (orderHref.startsWith('http') ? orderHref : BASE + orderHref) : null)
      : BASE + route.url;
    if (!url) { console.log(`skip ${route.id}: no order`); continue; }
    try {
      await page.goto(url, { waitUntil: 'load', timeout: 30_000 });
      // small settle for fonts/JS hydration
      await page.waitForTimeout(800);
      // verify still authenticated (didn't redirect to login)
      const stillAuthed = await page.evaluate(() => !document.querySelector('input[name=email][autofocus]'));
      if (!stillAuthed) {
        console.error(`AUTH LOST on ${route.id} — re-login`);
        await page.goto(BASE + '/admin/login', { waitUntil: 'load' });
        await page.fill('input[name=email]', EMAIL);
        await page.fill('input[name=password]', PASSWORD);
        await Promise.all([page.waitForURL(/\/admin/, { timeout: 30_000 }), page.click('button[type=submit]')]);
        await page.goto(url, { waitUntil: 'load', timeout: 30_000 });
        await page.waitForTimeout(800);
      }
      if (route.action === 'submitEmpty') {
        try {
          await page.click('form button[type=submit]', { timeout: 5_000 });
          await page.waitForLoadState('load', { timeout: 10_000 });
          await page.waitForTimeout(500);
        } catch (e) {}
      }
      const out = path.join(OUT, `${route.id}__desktop-1440.png`);
      await page.screenshot({ path: out, fullPage: true });
      console.log(`shot: ${route.id}`);
    } catch (e) {
      console.error(`ERR ${route.id}: ${e.message}`);
    }
  }

  await browser.close();
})();
