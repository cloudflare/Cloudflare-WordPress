/**
 * Records the JavaScript and console errors a site shows without the
 * Cloudflare plugin, as the baseline for a compatibility run.
 *
 * scripts/compatibility-tests.sh runs this with the third-party plugin active
 * and Cloudflare deactivated, then runs the browser tests with Cloudflare
 * active and BROWSER_ERROR_BASELINE pointing at the result. Only errors that
 * are not in the baseline fail those tests.
 *
 * Every page is opened twice, so errors that only happen sometimes still end
 * up in the baseline.
 *
 * Usage: WP_ENV_CONFIG=.wp-env.compat.json node tests/E2E/Support/RecordBrowserErrors.js <output.json>
 */

const { chromium, devices } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { baseURL } = require('./Environment');
const { collectErrors } = require('./BrowserErrors');
const globalSetup = require('./GlobalSetup');

// The pages the browser tests visit, plus a regular admin page, as the
// Cloudflare settings page does not exist without the plugin.
const { defaultBrowserType, ...iPhone } = devices['iPhone 15'];
const visits = [
	{ path: '/', context: {} },
	{ path: '/', context: iPhone },
	{ path: '/', context: { storageState: globalSetup.adminState } },
	{ path: '/wp-admin/options-general.php', context: { storageState: globalSetup.adminState } },
];

(async () => {
	const output = process.argv[2];
	if (!output) {
		process.stderr.write('Usage: node tests/E2E/Support/RecordBrowserErrors.js <output.json>\n');
		process.exit(1);
	}

	await globalSetup();
	const browser = await chromium.launch();
	const errors = new Set();

	for (const visit of visits) {
		for (let attempt = 0; attempt < 2; attempt++) {
			const context = await browser.newContext({ baseURL, ...visit.context });
			const page = await context.newPage();
			const pageErrors = collectErrors(page);
			await page.goto(visit.path);
			await page.waitForLoadState('networkidle');
			pageErrors.forEach((error) => errors.add(error));
			await context.close();
		}
	}

	await browser.close();

	fs.mkdirSync(path.dirname(output), { recursive: true });
	fs.writeFileSync(output, JSON.stringify([...errors].sort(), null, 2) + '\n');
	process.stdout.write(`${errors.size} browser error(s) without Cloudflare\n`);
	[...errors].sort().forEach((error) => process.stdout.write(`  ${error}\n`));
})().catch((error) => {
	process.stderr.write(`${error.stack}\n`);
	process.exit(1);
});
