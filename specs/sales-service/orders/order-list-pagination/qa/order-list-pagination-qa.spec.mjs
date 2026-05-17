/**
 * QA: Order List Pagination
 * Spec: specs/sales-service/orders/order-list-pagination/spec.md
 *
 * TC-001–TC-005: API pagination (metadata, page 2, clamp, validation)
 * TC-006–TC-010: Browser UI (pager, navigation, refresh, post-create page 1)
 */

import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const QA_DIR = path.dirname(fileURLToPath(import.meta.url));
const SCREENSHOTS = path.join(QA_DIR, 'screenshots');
const RESPONSES = path.join(QA_DIR, 'responses');
const RUN = Date.now().toString(36).toUpperCase();
const PER_PAGE = 20;
const API_HEADERS = { Accept: 'application/json' };

fs.mkdirSync(SCREENSHOTS, { recursive: true });
fs.mkdirSync(RESPONSES, { recursive: true });

let step = 0;
async function shot(page, label) {
  step += 1;
  const filename = `${String(step).padStart(2, '0')}-${label}.png`;
  await page.screenshot({ path: path.join(SCREENSHOTS, filename), fullPage: true });
}

function saveResponse(name, data) {
  fs.writeFileSync(path.join(RESPONSES, name), JSON.stringify(data, null, 2));
}

async function createOrder(request, suffix) {
  const res = await request.post('/svc/sales/api/orders', {
    headers: API_HEADERS,
    data: {
      customer_name: `QA Paginate ${suffix}`,
      customer_email: `qa-paginate-${suffix}@example.com`,
      total_amount: 10,
      items: [{ product_name: 'QA Widget', quantity: 1, unit_price: 10 }],
    },
  });
  expect(res.ok(), `create order failed: ${res.status()}`).toBeTruthy();
  return res.json();
}

async function ensureMinimumOrders(request, minTotal) {
  let created = 0;
  for (let attempt = 0; attempt < 40; attempt++) {
    const listRes = await request.get('/svc/sales/api/orders?page=1', { headers: API_HEADERS });
    expect(listRes.ok()).toBeTruthy();
    const list = await listRes.json();
    if (list.total >= minTotal) {
      return list;
    }
    const batch = Math.min(5, minTotal - list.total);
    for (let i = 0; i < batch; i++) {
      await createOrder(request, `${RUN}-${created++}`);
    }
    await new Promise((r) => setTimeout(r, 800));
  }
  const finalRes = await request.get('/svc/sales/api/orders?page=1', { headers: API_HEADERS });
  const finalBody = await finalRes.json();
  expect(finalBody.total, `expected at least ${minTotal} orders after seeding`).toBeGreaterThanOrEqual(minTotal);
  return finalBody;
}

async function pickFirstInventoryItem(page) {
  const select = page.locator('select').first();
  await expect(select).toBeAttached({ timeout: 15_000 });
  const options = await select.locator('option').all();
  if (options.length < 2) return null;
  await select.selectOption({ index: 1 });
  return true;
}

test.describe.serial('Order list pagination QA', () => {
  test('TC-001 — API page 1 returns pagination metadata', async ({ request }) => {
    const res = await request.get('/svc/sales/api/orders?page=1', { headers: API_HEADERS });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    saveResponse('api-tc001-page1.json', body);

    expect(body).toMatchObject({
      current_page: 1,
      per_page: PER_PAGE,
    });
    expect(typeof body.total).toBe('number');
    expect(typeof body.last_page).toBe('number');
    expect(Array.isArray(body.data)).toBeTruthy();
  });

  test('TC-004 — API rejects page=0 with 422', async ({ request }) => {
    const res = await request.get('/svc/sales/api/orders?page=0', { headers: API_HEADERS });
    expect(res.status()).toBe(422);
    saveResponse('api-tc004-page0.json', { status: res.status(), body: await res.json().catch(() => ({})) });
  });

  test('TC-005 — API rejects non-integer page with 422', async ({ request }) => {
    const res = await request.get('/svc/sales/api/orders?page=abc', { headers: API_HEADERS });
    expect(res.status()).toBe(422);
    saveResponse('api-tc005-page-abc.json', { status: res.status(), body: await res.json().catch(() => ({})) });
  });

  test('TC-002 — API page 2 returns second slice when total > 20', async ({ request }) => {
    const page1 = await ensureMinimumOrders(request, 21);
    saveResponse('api-tc002-seed-page1.json', { total: page1.total, last_page: page1.last_page });

    expect(page1.last_page).toBeGreaterThanOrEqual(2);

    const res = await request.get('/svc/sales/api/orders?page=2', { headers: API_HEADERS });
    expect(res.ok()).toBeTruthy();
    const page2 = await res.json();
    saveResponse('api-tc002-page2.json', page2);

    expect(page2.current_page).toBe(2);
    expect(page2.data.length).toBeGreaterThanOrEqual(1);

    const page1Ids = new Set(page1.data.map((o) => o.id));
    const overlap = page2.data.filter((o) => page1Ids.has(o.id));
    expect(overlap.length).toBe(0);
  });

  test('TC-003 — API clamps page beyond last to valid page', async ({ request }) => {
    const page1 = await request.get('/svc/sales/api/orders?page=1', { headers: API_HEADERS });
    const meta = await page1.json();
    const res = await request.get('/svc/sales/api/orders?page=9999', { headers: API_HEADERS });
    expect(res.ok()).toBeTruthy();
    const body = await res.json();
    saveResponse('api-tc003-clamp.json', body);

    expect(body.current_page).toBeLessThanOrEqual(Math.max(1, meta.last_page));
    expect(body.data.length).toBeGreaterThan(0);
  });

  test('TC-006 — UI shows pager when multiple pages exist', async ({ page, request }) => {
    const meta = await ensureMinimumOrders(request, 21);
    test.skip(meta.last_page < 2, 'Need 21+ orders for multi-page UI');

    await page.goto('/orders', { waitUntil: 'networkidle' });
    await expect(page.getByRole('heading', { name: 'Orders' })).toBeVisible();
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20_000 });

    await expect(page.getByText(/Page\s+1\s+of/i)).toBeVisible();
    const nextBtn = page.getByRole('button', { name: /Next/i }).first();
    await expect(nextBtn).toBeEnabled();

    await shot(page, 'tc006-page1-pager');
  });

  test('TC-007 — UI next page shows different orders and page indicator', async ({ page }) => {
    await page.goto('/orders', { waitUntil: 'networkidle' });
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 20_000 });

    const page1FirstId = await page.locator('tbody tr').first().locator('td').first().textContent();
    await page.getByRole('button', { name: /Next/i }).first().click();
    await expect(page.getByText(/Page\s+2\s+of/i)).toBeVisible({ timeout: 10_000 });

    const page2FirstId = await page.locator('tbody tr').first().locator('td').first().textContent();
    expect(page2FirstId).not.toBe(page1FirstId);

    await shot(page, 'tc007-page2');
  });

  test('TC-008 — UI previous returns to page 1', async ({ page }) => {
    await page.goto('/orders', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Next/i }).first().click();
    await expect(page.getByText(/Page\s+2\s+of/i)).toBeVisible({ timeout: 10_000 });
    await page.getByRole('button', { name: /Prev/i }).first().click();
    await expect(page.getByText(/Page\s+1\s+of/i)).toBeVisible({ timeout: 10_000 });
    await shot(page, 'tc008-back-page1');
  });

  test('TC-009 — Refresh on page 2 keeps page 2', async ({ page }) => {
    await page.goto('/orders', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Next/i }).first().click();
    await expect(page.getByText(/Page\s+2\s+of/i)).toBeVisible();

    await page.getByRole('button', { name: /Refresh/i }).click();
    await expect(page.locator('tbody tr').first()).toBeVisible();
    await expect(page.getByText(/Page\s+2\s+of/i)).toBeVisible();

    await shot(page, 'tc009-refresh-page2');
  });

  test('TC-010 — Create order from page 2 navigates to page 1', async ({ page }) => {
    const customerName = `QA Paginate UI ${RUN}`;

    await page.goto('/orders', { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: /Next/i }).first().click();
    await expect(page.getByText(/Page\s+2\s+of/i)).toBeVisible({ timeout: 10_000 });

    await page.getByRole('button', { name: '+ New Order' }).click();
    await expect(page.getByRole('heading', { name: 'Create New Order' })).toBeVisible();

    await page.locator('form input[type="text"]').first().fill(customerName);
    await page.locator('form input[type="email"]').first().fill(`qa-ui-${RUN}@example.com`);

    const picked = await pickFirstInventoryItem(page);
    test.skip(!picked, 'No inventory items for order form');

    await page.getByRole('button', { name: 'Create Order (Start Workflow)' }).click();
    await expect(page.locator('div.mb-4.rounded-lg.bg-green-50').first()).toBeVisible({ timeout: 20_000 });

    await expect(page.getByText(/Page\s+1\s+of/i)).toBeVisible({ timeout: 10_000 });
    await expect(page.locator('tbody').getByText(customerName)).toBeVisible({ timeout: 15_000 });

    await shot(page, 'tc010-after-create-page1');
  });
});
