/**
 * The front page in a real browser, with the Cloudflare plugin active: it
 * must load without JavaScript errors and with the right caching header.
 */

const { devices } = require('@playwright/test');
const { test, expect } = require('./Support/Fixtures');
const { adminState } = require('./Support/GlobalSetup');

/**
 * Open the front page and wait until its scripts have run.
 *
 * @param {import('@playwright/test').Page} page
 * @return {Promise<import('@playwright/test').Response>}
 */
async function openFrontPage(page) {
	const response = await page.goto('/');
	await page.waitForLoadState('networkidle');

	return response;
}

test.describe('Front page', () => {
	test('loads for visitors and may be cached', async ({ page }) => {
		const response = await openFrontPage(page);

		expect(response.status()).toBe(200);
		expect(response.headers()['cf-edge-cache']).toBe('cache,platform=wordpress');
	});

	test.describe('on a phone', () => {
		// The device's viewport, user agent and touch support; the browser
		// stays Chromium.
		const { defaultBrowserType, ...iPhone } = devices['iPhone 15'];
		test.use(iPhone);

		test('loads for mobile visitors and may be cached', async ({ page }) => {
			const response = await openFrontPage(page);

			expect(response.status()).toBe(200);
			expect(response.headers()['cf-edge-cache']).toBe('cache,platform=wordpress');
		});
	});

	test.describe('logged in', () => {
		test.use({ storageState: adminState });

		test('loads for logged-in users and is not cached', async ({ page }) => {
			const response = await openFrontPage(page);

			expect(response.status()).toBe(200);
			expect(response.headers()['cf-edge-cache']).toBe('no-cache');
		});
	});
});
