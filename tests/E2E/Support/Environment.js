/**
 * The wp-env environment the browser tests run against, and WP-CLI access to
 * it for setting up state.
 *
 * WP_ENV_CONFIG selects the environment: .wp-env.test.json (default),
 * .wp-env.build.json or .wp-env.compat.json. The site URL is read from that
 * config's WP_HOME.
 */

const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../../..');
const configFile = process.env.WP_ENV_CONFIG || '.wp-env.test.json';
const config = JSON.parse(fs.readFileSync(path.join(root, configFile), 'utf8'));

/** Credentials the Cloudflare API mock accepts (see tests/Fixtures/MuPlugins/CloudflareApiMock.php). */
const credentials = {
	email: 'integration@example.com',
	apiKey: 'c2547eb745079dac9320b638f5e225cf483cc',
};

const baseURL = config.config.WP_HOME;

/**
 * Run a command in the environment's cli container, from the WordPress root.
 *
 * @param {...string} command
 * @return {string} The command's output.
 */
function cli(...command) {
	return execFileSync('npx', ['wp-env', `--config=${configFile}`, 'run', 'cli', ...command], {
		cwd: root,
		encoding: 'utf8',
		stdio: ['ignore', 'pipe', 'pipe'],
	}).trim();
}

/**
 * Store Cloudflare credentials, as a successful sign-in does.
 */
function signInToCloudflare() {
	cli('wp', 'option', 'update', 'cloudflare_api_key', credentials.apiKey);
	cli('wp', 'option', 'update', 'cloudflare_api_email', credentials.email);
	cli('wp', 'option', 'update', 'cloudflare_cached_domain_name', new URL(baseURL).hostname);
}

/**
 * Remove the stored Cloudflare credentials, so the settings page asks to sign in.
 */
function signOutOfCloudflare() {
	for (const option of ['cloudflare_api_key', 'cloudflare_api_email', 'cloudflare_cached_domain_name']) {
		try {
			cli('wp', 'option', 'delete', option);
		} catch (error) {
			// The option did not exist.
		}
	}
}

/**
 * Empty the log of Cloudflare API calls the mock answered.
 */
function clearApiLog() {
	cli('sh', '-c', ': > wp-content/cloudflare-api-mock.log');
}

/**
 * Make the API mock answer later, until resetApiDelays().
 *
 * @param {Object<string, number>} delays Milliseconds by API path suffix, for example { entitlements: 1500 }.
 */
function delayApiResponses(delays) {
	cli('wp', 'option', 'update', 'cloudflare_api_mock_delays', JSON.stringify(delays), '--format=json');
}

/**
 * Let the API mock answer right away again.
 */
function resetApiDelays() {
	try {
		cli('wp', 'option', 'delete', 'cloudflare_api_mock_delays');
	} catch (error) {
		// No delays were set.
	}
}

/**
 * Cloudflare API calls since the last clearApiLog().
 *
 * @return {{method: string, path: string, query: Object, body: *, mocked: boolean}[]}
 */
function apiCalls() {
	const log = cli('sh', '-c', 'cat wp-content/cloudflare-api-mock.log 2>/dev/null || true');

	return log
		.split('\n')
		.filter((line) => line.startsWith('{'))
		.map((line) => JSON.parse(line));
}

module.exports = {
	baseURL,
	configFile,
	credentials,
	cli,
	signInToCloudflare,
	signOutOfCloudflare,
	clearApiLog,
	delayApiResponses,
	resetApiDelays,
	apiCalls,
};
