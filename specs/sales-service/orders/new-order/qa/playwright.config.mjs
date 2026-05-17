/** @type {import('@playwright/test').PlaywrightTestConfig} */
export default {
  testDir: '.',
  testMatch: 'orders-qa.spec.mjs',
  timeout: 90_000,
  use: {
    baseURL: 'http://localhost:3000',
    headless: true,
    video: 'retain-on-failure',
    screenshot: 'on',
    trace: 'retain-on-failure',
  },
  outputDir: './test-results',
  reporter: [['list'], ['html', { outputFolder: './test-report', open: 'never' }]],
};
