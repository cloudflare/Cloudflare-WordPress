/**
 * The Cloudflare settings page (Settings > Cloudflare) in a real browser: the
 * settings application must load, sign in and render every tab without
 * JavaScript errors. Cloudflare API calls are answered by the API mock.
 */

const { test, expect } = require('./Support/Fixtures');
const { adminState } = require('./Support/GlobalSetup');
const { collectErrors, settingsAppBugs } = require('./Support/BrowserErrors');
const {
	baseURL,
	cli,
	credentials,
	delayApiResponses,
	resetApiDelays,
	signInToCloudflare,
	signOutOfCloudflare,
} = require('./Support/Environment');

const settingsPage = '/wp-admin/options-general.php?page=cloudflare';

test.use({ storageState: adminState });

test.describe('Cloudflare settings page', () => {
	test('asks to sign in without stored credentials, and signs in', async ({ page }) => {
		signOutOfCloudflare();

		await page.goto(settingsPage);
		const app = page.locator('#root');
		await app.getByText('here', { exact: true }).click();
		await app.locator('input[name="email"]').fill(credentials.email);
		await app.locator('input[name="apiKey"]').fill(credentials.apiKey);
		await app.getByRole('button', { name: 'Save API Credentials' }).click();

		await expect(app.getByText('Apply Recommended Cloudflare Settings for WordPress')).toBeVisible();
		expect(cli('wp', 'option', 'get', 'cloudflare_api_email')).toBe(credentials.email);
	});

	test('rejects an API key Cloudflare does not accept', async ({ page }) => {
		signOutOfCloudflare();

		await page.goto(settingsPage);
		const app = page.locator('#root');
		await app.getByText('here', { exact: true }).click();
		await app.locator('input[name="email"]').fill(credentials.email);
		await app.locator('input[name="apiKey"]').fill('0000000000000000000000000000000000000');
		await app.getByRole('button', { name: 'Save API Credentials' }).click();

		await expect(page.getByText('Email address or API key invalid.')).toBeVisible();
		await expect(app.getByRole('button', { name: 'Save API Credentials' })).toBeVisible();
		expect(cli('wp', 'option', 'get', 'cloudflare_api_key')).toBe('');
	});

	test('shows the Home, Settings and Analytics tabs for the site zone', async ({ page }) => {
		signInToCloudflare();

		await page.goto(settingsPage);
		const app = page.locator('#root');

		await expect(app.getByText(new URL(baseURL).hostname).first()).toBeVisible();
		await expect(app.getByText('Apply Recommended Cloudflare Settings for WordPress')).toBeVisible();
		await expect(app.getByText('Automatic Platform Optimization').first()).toBeVisible();
		await expect(app.getByRole('button', { name: 'Purge Cache' })).toBeVisible();

		await app.getByText('Settings', { exact: true }).click();
		await expect(app.getByText('Always Online™')).toBeVisible();

		await app.getByText('Analytics', { exact: true }).click();
		await expect(app.getByText('Zone Analytics')).toBeVisible();
	});
});

// Known bugs in compiled.js (see settingsAppBugs). Each test makes its bug
// happen and fails once compiled.js is fixed: then remove the test and the
// entry in settingsAppBugs.
test.describe('Known settings app bugs', () => {
	test.afterEach(() => resetApiDelays());

	test('the APO card throws when the entitlements arrive after the zone settings', async ({ page }) => {
		const bug = settingsAppBugs.entitlementsNotLoaded;
		signInToCloudflare();
		delayApiResponses({ entitlements: 1500 });
		const errors = collectErrors(page);

		await page.goto(settingsPage);
		await expect(page.locator('#root').getByText('Automatic Platform Optimization').first()).toBeVisible();

		expect(errors, 'Fixed in compiled.js? Remove this test and settingsAppBugs.entitlementsNotLoaded').toContain(bug.error);
	});
});
