/**
 * Collecting JavaScript and console errors from a page, in a form that can be
 * compared between runs.
 *
 * Compatibility runs first record the errors a third-party plugin causes on its
 * own, with Cloudflare deactivated (see RecordBrowserErrors.js). The browser
 * tests then only fail on errors that are not in that baseline.
 */

const fs = require('fs');

/**
 * Known bugs in the settings application. compiled.js is built in the
 * separate cloudflare-plugin-frontend repository and cannot be fixed here.
 * The browser tests list these errors in the report instead of failing on
 * them. Every entry has a test in SettingsPage.spec.js that makes the bug
 * happen and fails once compiled.js is fixed, as a reminder to remove it.
 *
 * @type {Object<string, {description: string, error: string}>}
 */
const settingsAppBugs = {
	entitlementsNotLoaded: {
		description: 'The APO card reads the zone entitlements before they have loaded',
		error: "Uncaught TypeError: Cannot read properties of undefined (reading 'zone.automatic_platform_optimization')",
	},
};

/**
 * Start collecting errors from a page.
 *
 * @param {import('@playwright/test').Page} page
 * @return {string[]} Filled as errors happen.
 */
function collectErrors(page) {
	const errors = [];

	page.on('pageerror', (error) => errors.push(`Uncaught ${error.name}: ${error.message}`));
	page.on('console', (message) => {
		if (message.type() !== 'error') {
			return;
		}
		const url = withoutQuery(message.location().url);
		errors.push(`Console error: ${message.text()}${url ? ` (${url})` : ''}`);
	});

	return errors;
}

/**
 * The URL without its query string, which holds nonces, versions and cache
 * busters that differ between runs, and without the site address.
 *
 * @param {string} url
 * @return {string}
 */
function withoutQuery(url) {
	if (!url) {
		return '';
	}
	try {
		const parsed = new URL(url);

		return parsed.pathname;
	} catch (error) {
		return url.split('?')[0];
	}
}

/**
 * Errors recorded without Cloudflare, from the file in BROWSER_ERROR_BASELINE.
 *
 * @return {Set<string>}
 */
function baselineErrors() {
	const file = process.env.BROWSER_ERROR_BASELINE;
	if (!file || !fs.existsSync(file)) {
		return new Set();
	}

	return new Set(JSON.parse(fs.readFileSync(file, 'utf8')));
}

module.exports = { collectErrors, baselineErrors, settingsAppBugs };
