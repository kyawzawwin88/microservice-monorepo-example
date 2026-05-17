# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: location-management-qa.spec.mjs >> Warehouse Location Management — full user journey >> US1 — page loads with heading and location list
- Location: location-management-qa.spec.mjs:72:3

# Error details

```
Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:3000/inventory/locations
Call log:
  - navigating to "http://localhost:3000/inventory/locations", waiting until "domcontentloaded"

```

# Test source

```ts
  1   | /**
  2   |  * E2E: Warehouse Location Management — complete user journey
  3   |  *
  4   |  * Covers every user story from the spec:
  5   |  *   US1  View storage locations
  6   |  *   US2  Create storage location (+ validation)
  7   |  *   US3  Edit location details  (+ validation)
  8   |  *   US4  Deactivate location (stock guard + success)
  9   |  *   +    Show-inactive toggle
  10  |  *   +    Deactivated location absent from movement selectors
  11  |  *
  12  |  * Target: http://localhost:3000  (Vite dev / Docker UI)
  13  |  * API:    http://localhost:8004  (inventory-service)
  14  |  *
  15  |  * Run:
  16  |  *   npx playwright test --config=playwright.config.mjs
  17  |  */
  18  | 
  19  | import { test, expect } from '@playwright/test';
  20  | import fs from 'node:fs';
  21  | import path from 'node:path';
  22  | import { fileURLToPath } from 'node:url';
  23  | 
  24  | // ─── helpers ──────────────────────────────────────────────────────────────────
  25  | 
  26  | const QA_DIR       = path.dirname(fileURLToPath(import.meta.url));
  27  | const SCREENSHOTS  = path.join(QA_DIR, 'screenshots');
  28  | const RESPONSES    = path.join(QA_DIR, 'responses');
  29  | 
  30  | fs.mkdirSync(SCREENSHOTS, { recursive: true });
  31  | fs.mkdirSync(RESPONSES,   { recursive: true });
  32  | 
  33  | /** Unique suffix so each run creates fresh codes; avoids cross-run pollution */
  34  | const RUN  = Date.now().toString(36).toUpperCase();
  35  | const CODE_A    = `QA-A-${RUN}`;   // primary test location
  36  | const CODE_B    = `QA-B-${RUN}`;   // location with full address
  37  | const CODE_DEACT = `QA-D-${RUN}`;  // created solely for deactivation
  38  | 
  39  | const STOCKED_LOCATION_NAME = 'Main Warehouse'; // seeded location that holds stock
  40  | 
  41  | /** Save screenshot with a zero-padded step prefix */
  42  | let step = 0;
  43  | async function shot(page, label) {
  44  |   step++;
  45  |   const filename = `${String(step).padStart(2, '0')}-${label}.png`;
  46  |   await page.screenshot({ path: path.join(SCREENSHOTS, filename), fullPage: true });
  47  | }
  48  | 
  49  | /** Row locator in the locations table for a given code */
  50  | function rowForCode(page, code) {
  51  |   return page.locator('tbody tr').filter({ hasText: code });
  52  | }
  53  | 
  54  | /** Wait for and return the green success banner */
  55  | function successBanner(page) {
  56  |   return page.locator('.bg-green-50');
  57  | }
  58  | 
  59  | /** Wait for and return the red error banner */
  60  | function errorBanner(page) {
  61  |   return page.locator('.bg-red-50');
  62  | }
  63  | 
  64  | // ─── suite ────────────────────────────────────────────────────────────────────
  65  | 
  66  | test.describe.configure({ mode: 'serial' });
  67  | 
  68  | test.describe('Warehouse Location Management — full user journey', () => {
  69  | 
  70  |   // ── US1: View ───────────────────────────────────────────────────────────────
  71  | 
  72  |   test('US1 — page loads with heading and location list', async ({ page }) => {
> 73  |     await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
      |                ^ Error: page.goto: net::ERR_CONNECTION_REFUSED at http://localhost:3000/inventory/locations
  74  | 
  75  |     // Heading visible
  76  |     await expect(page.getByRole('heading', { name: 'Warehouse Location' })).toBeVisible();
  77  | 
  78  |     // Add location form present
  79  |     await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();
  80  | 
  81  |     // Required form fields are present
  82  |     await expect(page.locator('#create-name')).toBeVisible();
  83  |     await expect(page.locator('#create-code')).toBeVisible();
  84  | 
  85  |     // Navigation breadcrumb back to Inventory
  86  |     await expect(page.getByRole('link', { name: '← Inventory' })).toBeVisible();
  87  | 
  88  |     // Table header columns visible (if seeded locations exist)
  89  |     const tableHead = page.locator('thead th');
  90  |     const headCount = await tableHead.count();
  91  |     if (headCount > 0) {
  92  |       await expect(tableHead.filter({ hasText: 'Name' })).toBeVisible();
  93  |       await expect(tableHead.filter({ hasText: 'Code' })).toBeVisible();
  94  |       await expect(tableHead.filter({ hasText: 'Status' })).toBeVisible();
  95  |     }
  96  | 
  97  |     await shot(page, 'us1-page-load');
  98  |   });
  99  | 
  100 | 
  101 |   // ── US2a: Validation — missing required fields ──────────────────────────────
  102 | 
  103 |   test('US2a — validation: missing name shows field error', async ({ page }) => {
  104 |     await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
  105 |     await page.waitForSelector('#create-name');
  106 | 
  107 |     // Submit with only a code but no name
  108 |     await page.locator('#create-code').fill(CODE_A);
  109 |     await page.getByRole('button', { name: 'Create location' }).click();
  110 | 
  111 |     // Client-side validation should flag the name field
  112 |     const nameInput = page.locator('#create-name');
  113 |     await expect(nameInput).toHaveAttribute('aria-invalid', 'true');
  114 | 
  115 |     // Banner or inline error present
  116 |     const hasTopError  = await errorBanner(page).isVisible().catch(() => false);
  117 |     const hasNameError = await page.locator('#create-name ~ p.text-red-600').isVisible().catch(() => false);
  118 |     expect(hasTopError || hasNameError).toBeTruthy();
  119 | 
  120 |     await shot(page, 'us2a-missing-name');
  121 |   });
  122 | 
  123 | 
  124 |   // ── US2b: Validation — postal code without country ──────────────────────────
  125 | 
  126 |   test('US2b — validation: postal code without country is rejected', async ({ page }) => {
  127 |     await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
  128 |     await page.waitForSelector('#create-name');
  129 | 
  130 |     await page.locator('#create-name').fill('QA Postal No Country');
  131 |     await page.locator('#create-code').fill(`QA-NC-${RUN}`);
  132 |     await page.locator('#create-postal').fill('94105');
  133 |     // Leave country empty (default "— Select country —")
  134 | 
  135 |     await page.getByRole('button', { name: 'Create location' }).click();
  136 | 
  137 |     // Country field marked invalid
  138 |     const countrySelect = page.locator('#create-country');
  139 |     await expect(countrySelect).toHaveAttribute('aria-invalid', 'true');
  140 | 
  141 |     // Error visible near country or in banner
  142 |     const hasTopError     = await errorBanner(page).isVisible().catch(() => false);
  143 |     const hasCountryError = await page.locator('#create-country ~ p.text-red-600').isVisible().catch(() => false);
  144 |     expect(hasTopError || hasCountryError).toBeTruthy();
  145 | 
  146 |     await shot(page, 'us2b-postal-without-country');
  147 |   });
  148 | 
  149 | 
  150 |   // ── US2c: Create location (name + code only) ─────────────────────────────────
  151 | 
  152 |   test('US2c — create location with name and code', async ({ page }) => {
  153 |     await page.goto('/inventory/locations', { waitUntil: 'domcontentloaded' });
  154 |     await page.waitForSelector('#create-name');
  155 | 
  156 |     await page.locator('#create-name').fill('QA Test Warehouse A');
  157 |     await page.locator('#create-code').fill(CODE_A);
  158 |     await page.getByRole('button', { name: 'Create location' }).click();
  159 | 
  160 |     // Success banner
  161 |     await expect(successBanner(page)).toContainText('Location created', { timeout: 15_000 });
  162 | 
  163 |     // New row visible in table
  164 |     const row = rowForCode(page, CODE_A);
  165 |     await expect(row).toBeVisible({ timeout: 10_000 });
  166 |     await expect(row.getByText('QA Test Warehouse A')).toBeVisible();
  167 |     await expect(row.getByText('Active')).toBeVisible();
  168 | 
  169 |     // Form reset to "Add location" mode
  170 |     await expect(page.getByRole('heading', { name: 'Add location' })).toBeVisible();
  171 |     await expect(page.locator('#create-name')).toHaveValue('');
  172 | 
  173 |     // Save response
```