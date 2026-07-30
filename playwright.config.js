// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/E2E',
    timeout: 30000,
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: 2,
    reporter: [['list'], ['html', { outputFolder: 'tests/E2E/report', open: 'never' }]],
    use: {
        baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile',   use: { ...devices['iPhone 13'] } },
    ],
    webServer: {
        command: 'php -S 127.0.0.1:8000 -t public public/router.php',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: !process.env.CI,
        timeout: 10000,
    },
});
