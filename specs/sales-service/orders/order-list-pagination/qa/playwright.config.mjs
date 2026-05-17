/** @type {import('@playwright/test').PlaywrightTestConfig} */
export default {
  testDir: '.',
  testMatch: 'order-list-pagination-qa.spec.mjs',
  timeout: 120_000,
  use: {
    baseURL: 'http://localhost:3000',
    viewport: { width: 1400, height: 900 },
    headless: true,
    video: 'retain-on-failure',
    screenshot: 'on',
    trace: 'retain-on-failure',
  },
  outputDir: './test-results',
  reporter: [['list']],
};
