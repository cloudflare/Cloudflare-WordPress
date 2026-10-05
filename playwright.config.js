/**
 * Browser tests (Playwright). They run against a wp-env environment, the test
 * environment by default. Set WP_ENV_CONFIG to use another one, for example
 * WP_ENV_CONFIG=.wp-env.build.json.
 *
 * See docs/testing.md.
 */

const { defineConfig, devices } = require('@playwright/test');
const { baseURL } = require('./tests/E2E/Support/Environment');

module.exports = defineConfig({
	testDir: './tests/E2E',
	testMatch: '*.spec.js',
	outputDir: './tests/E2E/.results',

	// The tests share one WordPress site and change its settings.
	workers: 1,
	fullyParallel: false,

	forbidOnly: !!process.env.CI,
	reporter: process.env.CI ? [['list'], ['github']] : 'list',
	globalSetup: require.resolve('./tests/E2E/Support/GlobalSetup'),

	use: {
		baseURL,
		screenshot: 'only-on-failure',
		trace: 'retain-on-failure',
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
	],
});
