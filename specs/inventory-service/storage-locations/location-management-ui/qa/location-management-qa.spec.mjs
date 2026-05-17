/**
 * E2E: Warehouse Location Management — complete user journey
 *
 * Covers every user story from the spec:
 *   US1  View storage locations
 *   US2  Create storage location (+ validation)
 *   US3  Edit location details  (+ validation)
 *   US4  Deactivate location (stock guard + success)
 *   +    Show-inactive toggle
 *   +    Deactivated location absent from movement selectors
 *
 * Target: http://localhost:3000  (Vite dev / Docker UI)
 * API:    http://localhost:8004  (inventory-service)
 *
 * Run:
 *   npx playwright test --config=playwright.config.mjs
 */

import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// ─── helpers ──────────────────────────────────────────────────────────────────

const QA_DIR       = path.dirname(fileURLToPath(import.meta.url));
const SCREENSHOTS  = path.join(QA_DIR, 'screenshots');
const RESPONSES    = path.join(QA_DIR, 'responses');

fs.mkdirSync(SCREENSHOTS, { recursive: true });
fs.mkdirSync(RESPONSES,   { recursive: true });

/** Unique suffix so each run creates fresh codes; avoids cross-run pollution */
const RUN  = Date.now().toString(36).toUpperCase();
const CODE_A    = `QA-A-${RUN}`;   // primary test location
const CODE_B    = `QA-B-${RUN}`;   // location with full address
const CODE_DEACT = `QA-D-${RUN}`;  // created solely for deactivation

const STOCKED_LOCATION_NAME = 'Main Warehouse'; // seeded location that holds stock

/** Save screenshot with a zero-padded step prefix */
let step = 0;
async function shot(page, label) {
  step++;
  const filename = `${String(step).padStart(2, '0')}-${label}.png`;
  await page.screenshot({ path: path.join(SCREENSHOTS, filename), fullPage: true });
}

/** Row locator in the locations table for a given code */
function rowForCode(page, code) {
  return page.locator('tbody tr').filter({ hasText: code });
}

/** Wait for and return the green success banner */
function successBanner(page) {
  return page.locator('.bg-green-50');
}

/** Wait for and return the red error banner */
function errorBanner(page) {
  return page.locator('.bg-red-50');
}

// ─── suite ────────────────────────────────────────────────────────────────────

test.describe.configure({ mode: 'serial' });

test.describe('Warehouse Location Management — full user journey', () => {

  // ── US1: View ───────────────────────────────────────────────────────────────

  test('US1 — page loads with heading and location list', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });

    // Heading visible
    await expect(page.getByRole('heading', { name: 'Warehouse Location' })).toBeVisible();

    // Add location form present
    await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();

    // Required form fields are present
    await expect(page.locator('#create-name')).toBeVisible();
    await expect(page.locator('#create-code')).toBeVisible();

    // Navigation breadcrumb back to Inventory
    await expect(page.getByRole('link', { name: '← Inventory' })).toBeVisible();

    // Table header columns visible (if seeded locations exist)
    const tableHead = page.locator('thead th');
    const headCount = await tableHead.count();
    if (headCount > 0) {
      await expect(tableHead.filter({ hasText: 'Name' })).toBeVisible();
      await expect(tableHead.filter({ hasText: 'Code' })).toBeVisible();
      await expect(tableHead.filter({ hasText: 'Status' })).toBeVisible();
    }

    await shot(page, 'us1-page-load');
  });


  // ── US2a: Validation — missing required fields ──────────────────────────────

  test('US2a — validation: missing name shows field error', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    // Submit with only a code but no name
    await page.locator('#create-code').fill(CODE_A);
    await page.getByRole('button', { name: 'Create location' }).click();

    // Client-side validation should flag the name field
    const nameInput = page.locator('#create-name');
    await expect(nameInput).toHaveAttribute('aria-invalid', 'true');

    // Banner or inline error present
    const hasTopError  = await errorBanner(page).isVisible().catch(() => false);
    const hasNameError = await page.locator('#create-name ~ p.text-red-600').isVisible().catch(() => false);
    expect(hasTopError || hasNameError).toBeTruthy();

    await shot(page, 'us2a-missing-name');
  });


  // ── US2b: Validation — postal code without country ──────────────────────────

  test('US2b — validation: postal code without country is rejected', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    await page.locator('#create-name').fill('QA Postal No Country');
    await page.locator('#create-code').fill(`QA-NC-${RUN}`);
    await page.locator('#create-postal').fill('94105');
    // Leave country empty (default "— Select country —")

    await page.getByRole('button', { name: 'Create location' }).click();

    // Country field marked invalid
    const countrySelect = page.locator('#create-country');
    await expect(countrySelect).toHaveAttribute('aria-invalid', 'true');

    // Error visible near country or in banner
    const hasTopError     = await errorBanner(page).isVisible().catch(() => false);
    const hasCountryError = await page.locator('#create-country ~ p.text-red-600').isVisible().catch(() => false);
    expect(hasTopError || hasCountryError).toBeTruthy();

    await shot(page, 'us2b-postal-without-country');
  });


  // ── US2c: Create location (name + code only) ─────────────────────────────────

  test('US2c — create location with name and code', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    await page.locator('#create-name').fill('QA Test Warehouse A');
    await page.locator('#create-code').fill(CODE_A);
    await page.getByRole('button', { name: 'Create location' }).click();

    // Success banner
    await expect(successBanner(page)).toContainText('Location created', { timeout: 15_000 });

    // New row visible in table
    const row = rowForCode(page, CODE_A);
    await expect(row).toBeVisible({ timeout: 10_000 });
    await expect(row.getByText('QA Test Warehouse A')).toBeVisible();
    await expect(row.getByText('Active')).toBeVisible();

    // Form reset to "Add location" mode
    await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();
    await expect(page.locator('#create-name')).toHaveValue('');

    // Save response
    const apiRes = await page.request.get('/svc/inventory/api/inventory/locations?all=1');
    const body = await apiRes.json();
    fs.writeFileSync(
      path.join(RESPONSES, 'locations-after-create.json'),
      JSON.stringify(body, null, 2),
    );
    const created = body.data.find((l) => l.code === CODE_A);
    expect(created).toBeTruthy();
    expect(created.is_active).toBe(true);

    await shot(page, 'us2c-create-success');
  });


  // ── US2d: Duplicate code rejected ────────────────────────────────────────────

  test('US2d — create: duplicate code is rejected', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    await page.locator('#create-name').fill('QA Duplicate Attempt');
    await page.locator('#create-code').fill(CODE_A); // already exists
    await page.getByRole('button', { name: 'Create location' }).click();

    // Error appears (API 422 or client duplicate check)
    await expect(errorBanner(page)).toBeVisible({ timeout: 10_000 });
    await expect(errorBanner(page)).toContainText(/code|duplicate|already/i);

    // No second row with same code
    const rows = page.locator('tbody tr').filter({ hasText: CODE_A });
    await expect(rows).toHaveCount(1);

    await shot(page, 'us2d-duplicate-code');
  });


  // ── US2e: Create location with full address ───────────────────────────────────

  test('US2e — create location with street, country and postal code', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    await page.locator('#create-name').fill('QA Test Warehouse B');
    await page.locator('#create-code').fill(CODE_B);
    await page.locator('#create-street').fill('100 Market Street\nSuite 5');
    await page.locator('#create-country').selectOption('US');
    await page.locator('#create-postal').fill('94105');
    await page.getByRole('button', { name: 'Create location' }).click();

    await expect(successBanner(page)).toContainText('Location created', { timeout: 15_000 });

    const row = rowForCode(page, CODE_B);
    await expect(row).toBeVisible({ timeout: 10_000 });

    // Address summary appears in the Address column
    await expect(row.locator('td').nth(2)).not.toBeEmpty();

    // Verify via API that address parts were persisted
    const apiRes = await page.request.get('/svc/inventory/api/inventory/locations?all=1');
    const body = await apiRes.json();
    fs.writeFileSync(
      path.join(RESPONSES, 'locations-after-create-with-address.json'),
      JSON.stringify(body, null, 2),
    );
    const created = body.data.find((l) => l.code === CODE_B);
    expect(created?.country_code).toBe('US');
    expect(created?.postal_code).toBe('94105');

    await shot(page, 'us2e-create-with-address');
  });


  // ── US3a: Edit — update name ──────────────────────────────────────────────────

  test('US3a — edit: update location name', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    // Click Edit on the CODE_A row
    const row = rowForCode(page, CODE_A);
    await expect(row).toBeVisible({ timeout: 10_000 });
    await row.getByRole('button', { name: 'Edit' }).click();

    // Form switches to Edit mode
    await expect(page.getByRole('heading', { name: 'Edit location' })).toBeVisible();

    // Update the name
    const nameInput = page.locator('#edit-name');
    await nameInput.fill('');
    await nameInput.fill('QA Warehouse A Renamed');
    await page.getByRole('button', { name: 'Save changes' }).click();

    await expect(successBanner(page)).toContainText('Location updated', { timeout: 15_000 });

    // Updated name in list
    const updatedRow = rowForCode(page, CODE_A);
    await expect(updatedRow.getByText('QA Warehouse A Renamed')).toBeVisible({ timeout: 10_000 });

    // Form back to Add mode
    await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();

    // Verify via API
    const apiRes = await page.request.get('/svc/inventory/api/inventory/locations?all=1');
    const body = await apiRes.json();
    fs.writeFileSync(
      path.join(RESPONSES, 'locations-after-rename.json'),
      JSON.stringify(body, null, 2),
    );
    const updated = body.data.find((l) => l.code === CODE_A);
    expect(updated?.name).toBe('QA Warehouse A Renamed');

    await shot(page, 'us3a-edit-name');
  });


  // ── US3b: Edit — duplicate code rejected ─────────────────────────────────────

  test('US3b — edit: changing to a duplicate code is rejected', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    // Edit CODE_A, try to change its code to CODE_B (which exists)
    const row = rowForCode(page, CODE_A);
    await expect(row).toBeVisible({ timeout: 10_000 });
    await row.getByRole('button', { name: 'Edit' }).click();
    await expect(page.getByRole('heading', { name: 'Edit location' })).toBeVisible();

    const codeInput = page.locator('#edit-code');
    await codeInput.fill('');
    await codeInput.fill(CODE_B); // duplicate
    await page.getByRole('button', { name: 'Save changes' }).click();

    await expect(errorBanner(page)).toBeVisible({ timeout: 10_000 });
    await expect(errorBanner(page)).toContainText(/code|duplicate|already/i);

    // CODE_A row still intact
    await expect(rowForCode(page, CODE_A)).toBeVisible();

    // Cancel edit cleanly
    await page.getByRole('button', { name: 'Cancel' }).click();
    await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();

    await shot(page, 'us3b-edit-duplicate-code');
  });


  // ── US4a: Deactivate blocked when stock remains ───────────────────────────────

  test('US4a — deactivate blocked when location has stock', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    // Find the stocked location row
    const row = page.locator('tbody tr').filter({ hasText: STOCKED_LOCATION_NAME });
    const rowCount = await row.count();
    if (rowCount === 0) {
      test.skip(true, `"${STOCKED_LOCATION_NAME}" not found in list — seed data may differ`);
      return;
    }

    // Trigger deactivate
    await row.getByRole('button', { name: 'Deactivate' }).click();

    // Confirmation modal appears
    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 5_000 });
    await expect(modal.getByRole('heading', { name: 'Deactivate location?' })).toBeVisible();
    await modal.getByRole('button', { name: 'Deactivate' }).click();

    // Error banner — stock guard fires
    await expect(errorBanner(page)).toBeVisible({ timeout: 10_000 });
    await expect(errorBanner(page)).toContainText(/stock|transfer|clear/i);

    // Location still listed as Active
    await expect(row.getByText('Active')).toBeVisible();

    await shot(page, 'us4a-deactivate-blocked-by-stock');
  });


  // ── US4b: Deactivate empty location ──────────────────────────────────────────

  test('US4b — deactivate empty location succeeds', async ({ page }) => {
    // First: create a fresh location to deactivate (zero stock by definition)
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    await page.locator('#create-name').fill('QA Deactivate Me');
    await page.locator('#create-code').fill(CODE_DEACT);
    await page.getByRole('button', { name: 'Create location' }).click();
    await expect(successBanner(page)).toContainText('Location created', { timeout: 15_000 });

    const row = rowForCode(page, CODE_DEACT);
    await expect(row).toBeVisible({ timeout: 10_000 });

    // Deactivate it
    await row.getByRole('button', { name: 'Deactivate' }).click();

    const modal = page.getByRole('dialog');
    await expect(modal).toBeVisible({ timeout: 5_000 });
    await expect(modal.getByRole('heading', { name: 'Deactivate location?' })).toBeVisible();

    await shot(page, 'us4b-deactivate-modal');

    await modal.getByRole('button', { name: 'Deactivate' }).click();

    // Success banner
    await expect(successBanner(page)).toContainText('deactivated', { timeout: 15_000 });

    // Row disappears from the default (active-only) view
    await expect(rowForCode(page, CODE_DEACT)).toHaveCount(0, { timeout: 10_000 });

    // Verify via API — is_active should be false
    const apiRes = await page.request.get('/svc/inventory/api/inventory/locations?all=1');
    const body = await apiRes.json();
    fs.writeFileSync(
      path.join(RESPONSES, 'locations-after-deactivate.json'),
      JSON.stringify(body, null, 2),
    );
    const deactivated = body.data.find((l) => l.code === CODE_DEACT);
    expect(deactivated?.is_active).toBe(false);

    await shot(page, 'us4b-deactivate-success');
  });


  // ── US4c: Show-inactive toggle reveals deactivated location ──────────────────

  test('US4c — show-inactive toggle reveals the deactivated location', async ({ page }) => {
    await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#create-name');

    // Default: deactivated location should not be visible
    await expect(rowForCode(page, CODE_DEACT)).toHaveCount(0, { timeout: 10_000 });

    // Toggle "Show inactive"
    await page.getByLabel('Show inactive').check();

    // Row now visible with Inactive badge
    const row = rowForCode(page, CODE_DEACT);
    await expect(row).toBeVisible({ timeout: 10_000 });
    await expect(row.getByText('Inactive')).toBeVisible();

    // No "Deactivate" button on already-inactive row
    await expect(row.getByRole('button', { name: 'Deactivate' })).toHaveCount(0);

    await shot(page, 'us4c-show-inactive-toggle');

    // Uncheck — row disappears again
    await page.getByLabel('Show inactive').uncheck();
    await expect(rowForCode(page, CODE_DEACT)).toHaveCount(0, { timeout: 5_000 });

    await shot(page, 'us4c-hide-inactive');
  });


  // ── Movement selector exclusion ───────────────────────────────────────────────

  test('Movement selectors — deactivated location absent from stock transfer', async ({ page }) => {
    await page.goto('/inventory/transfer', { waitUntil: 'domcontentloaded' });

    // Wait for the location dropdowns to populate
    // Transfer page has source and destination location selects
    const selects = page.locator('select');
    await expect(selects.first()).toBeAttached({ timeout: 15_000 });

    // Collect all option text across all selects on the page
    const allOptions = await page.evaluate(() =>
      Array.from(document.querySelectorAll('select option')).map((o) => o.textContent ?? ''),
    );

    // Deactivated location must not appear in any selector
    const found = allOptions.some((text) => text.includes(CODE_DEACT));
    expect(found).toBe(false);

    // Active locations (CODE_A, CODE_B) should be present
    const foundA = allOptions.some((text) => text.includes(CODE_A));
    const foundB = allOptions.some((text) => text.includes(CODE_B));
    expect(foundA).toBe(true);
    expect(foundB).toBe(true);

    await shot(page, 'movement-selector-deactivated-absent');
  });

});
