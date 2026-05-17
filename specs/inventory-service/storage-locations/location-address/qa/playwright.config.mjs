/** @type {import('@playwright/test').PlaywrightTestConfig} */
export default {
  testDir: '.',
  testMatch: 'location-address-qa.spec.mjs',
  timeout: 90_000,
  use: {
    baseURL: 'http://localhost:3000',
    headless: true,
    video: 'on',
    screenshot: 'on',
    trace: 'off',
  },
  outputDir: './test-results',
  reporter: [['list']],
};
