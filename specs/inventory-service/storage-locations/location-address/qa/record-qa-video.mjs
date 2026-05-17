import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const QA_DIR = path.dirname(fileURLToPath(import.meta.url));
const screenshotsDir = path.join(QA_DIR, 'screenshots');
const responsesDir = path.join(QA_DIR, 'responses');
const videosDir = path.join(QA_DIR, 'videos');

for (const d of [screenshotsDir, responsesDir, videosDir]) fs.mkdirSync(d, { recursive: true });

const unique = Date.now().toString(36).toUpperCase();
const results = [];

async function shot(page, name) {
  const p = path.join(screenshotsDir, name);
  await page.screenshot({ path: p, fullPage: true });
  results.push({ step: name, screenshot: p });
}

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  recordVideo: { dir: videosDir, size: { width: 1280, height: 900 } },
});
const page = await context.newPage();

try {
  await page.goto('http://localhost:3000/inventory/locations', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#create-name', { timeout: 20_000 });
  await shot(page, '01-locations-page.png');

  await page.locator('#create-name').fill('QA Addr Invalid');
  await page.locator('#create-code').fill(`QA-BAD-${unique}`);
  await page.locator('#create-postal').fill('12345');
  await page.getByRole('button', { name: 'Create location' }).click();
  const countryErrCount = await page.getByText(/Country is required when a postal code/i).count();
  if (countryErrCount < 1) throw new Error('Expected country field error when postal without country');
  await shot(page, '02-postal-without-country.png');

  await page.locator('#create-country').selectOption('US');
  await page.locator('#create-street').fill('100 Market St\nSuite 5');
  await page.locator('#create-postal').fill('94105');
  await page.getByRole('button', { name: 'Create location' }).click();
  await page.getByText('Location created').waitFor({ timeout: 15_000 });
  await shot(page, '03-create-with-address.png');

  await page.getByRole('button', { name: 'Edit' }).first().click();
  await page.waitForSelector('#edit-street');
  await page.locator('#edit-street').fill('200 Updated Ave');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await page.getByText('Location updated').waitFor({ timeout: 15_000 });
  await shot(page, '04-edit-address.png');

  const apiRes = await page.request.get('http://localhost:3000/svc/inventory/api/inventory/locations?all=1');
  const body = await apiRes.json();
  fs.writeFileSync(path.join(responsesDir, 'locations-after-qa.json'), JSON.stringify(body, null, 2));
  const created = body.data.find((l) => l.code === `QA-BAD-${unique}`);
  if (!created || created.country_code !== 'US') throw new Error('API missing created location address');

  results.push({ step: 'api-verify', ok: true, code: created.code });
  console.log(JSON.stringify({ ok: true, unique, results }, null, 2));
} catch (err) {
  await shot(page, '99-error-state.png').catch(() => {});
  console.error(JSON.stringify({ ok: false, error: String(err), results }, null, 2));
  process.exitCode = 1;
} finally {
  await context.close();
  await browser.close();
  const webmFiles = fs.readdirSync(videosDir).filter((f) => f.endsWith('.webm'));
  if (webmFiles.length) {
    const src = path.join(videosDir, webmFiles[webmFiles.length - 1]);
    const dest = path.join(videosDir, 'location-address-qa-run.webm');
    fs.renameSync(src, dest);
    console.log('VIDEO:' + dest);
  }
}
