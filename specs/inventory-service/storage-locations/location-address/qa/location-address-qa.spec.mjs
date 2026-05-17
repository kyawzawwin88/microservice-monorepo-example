import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const QA_DIR = path.dirname(fileURLToPath(import.meta.url));
const screenshotsDir = path.join(QA_DIR, 'screenshots');
const responsesDir = path.join(QA_DIR, 'responses');

fs.mkdirSync(screenshotsDir, { recursive: true });
fs.mkdirSync(responsesDir, { recursive: true });

const unique = Date.now().toString(36).toUpperCase();

test('location address admin flows', async ({ page }) => {
  await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#create-name', { timeout: 15_000 });
  await expect(page.getByRole('heading', { name: 'Warehouse Location' })).toBeVisible();
  await page.screenshot({ path: path.join(screenshotsDir, '01-locations-page.png'), fullPage: true });

  await page.locator('#create-name').fill('QA Addr Invalid');
  await page.locator('#create-code').fill(`QA-BAD-${unique}`);
  await page.locator('#create-postal').fill('12345');
  await page.getByRole('button', { name: 'Create location' }).click();
  await expect(page.locator('#err-country')).toContainText(/Country/i);
  await page.screenshot({ path: path.join(screenshotsDir, '02-postal-without-country.png'), fullPage: true });

  await page.locator('#create-country').selectOption('US');
  await page.locator('#create-street').fill('100 Market St\nSuite 5');
  await page.locator('#create-postal').fill('94105');
  await page.getByRole('button', { name: 'Create location' }).click();
  await expect(page.getByText('Location created')).toBeVisible({ timeout: 15_000 });
  await expect(page.getByText('QA Addr Invalid')).toBeVisible();
  await page.screenshot({ path: path.join(screenshotsDir, '03-create-with-address.png'), fullPage: true });

  const editBtn = page.getByRole('button', { name: 'Edit' }).first();
  await editBtn.click();
  await expect(page.getByRole('heading', { name: 'Edit location' })).toBeVisible();
  await page.locator('#edit-street').fill('200 Updated Ave');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await expect(page.getByText('Location updated')).toBeVisible({ timeout: 15_000 });
  await page.screenshot({ path: path.join(screenshotsDir, '04-edit-address.png'), fullPage: true });

  const apiRes = await page.request.get('/svc/inventory/api/inventory/locations?all=1');
  const body = await apiRes.json();
  fs.writeFileSync(path.join(responsesDir, 'locations-after-qa.json'), JSON.stringify(body, null, 2));
  expect(apiRes.ok()).toBeTruthy();
  const created = body.data.find((l) => l.code === `QA-BAD-${unique}`);
  expect(created?.country_code).toBe('US');
  expect(created?.postal_code).toBe('94105');
});
