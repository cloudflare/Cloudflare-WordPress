# Testing and quality checks

The plugin has six test suites and two static checks. All of them run in CI
on every push and pull request.

| Suite | What it covers | Runs in | Command |
|---|---|---|---|
| Unit | Classes in `src/` without WordPress | Your PHP | `composer test` |
| Unit, build | The same tests against the PHP-Scoper build | Your PHP | `composer test:build` |
| Integration | The plugin inside a real WordPress install | wp-env (Docker) | `npm run test:integration` |
| Integration, build | The same tests against the build, next to a conflicting `psr/log` | wp-env (Docker) | `npm run test:integration:build` |
| Browser | The settings page and the front page in a real browser (Chromium), without JavaScript errors | wp-env (Docker) and Playwright | `npm run test:e2e` |
| Compatibility | The integration suite and the browser tests with one third-party plugin active next to Cloudflare, for each supported plugin | wp-env (Docker) and Playwright | `npm run test:compatibility` |

## Requirements

- PHP 7.4 or later and Composer 2, for the unit tests, PHPCS and PHPStan.
- Node.js 20 or later and Docker, for the integration and browser tests.
- Chromium for Playwright, for the browser tests: `npx playwright install chromium`.
- PHP-Scoper, for the build suites: `composer global require humbug/php-scoper:^0.18`
  (PHP-Scoper itself needs PHP 8.1 or later).

Install the dependencies once:

```sh
composer install
npm install
```

## Command reference

| Command | Does |
|---|---|
| `composer qa` | `lint`, `stan` and `test` in one go. Run this before opening a pull request. |
| `composer lint` | PHPCS, errors only. This is what CI enforces. |
| `composer lint:strict` | PHPCS including warnings. |
| `composer lint:fix` | Fix what PHPCBF can fix automatically. |
| `composer stan` | PHPStan static analysis. |
| `composer stan:baseline` | Regenerate `phpstan-baseline.neon` (see below before you do). |
| `composer test` | Unit suite. |
| `composer test:coverage` | Unit suite with coverage: HTML in `coverage/`, Clover in `coverage.xml`. Needs pcov or Xdebug. |
| `composer build` | Build the scoped plugin into `build/cloudflare`. |
| `composer test:build` | Unit suite against `build/cloudflare`. Run `composer build` first. |
| `npm run env:test:start` / `env:test:stop` | Start or stop the integration test environment. |
| `npm run test:integration` | Integration suite. |
| `npm run env:build:start` / `env:build:stop` | Start or stop the build test environment. |
| `npm run test:integration:build` | Integration suite against `build/cloudflare`. |
| `npm run test:e2e` | Browser tests against the test environment. |
| `npm run test:e2e:build` | Browser tests against the build environment. |
| `npm run env:compat:start` / `env:compat:stop` | Start or stop the compatibility environment. |
| `npm run test:compatibility` | Integration suite next to each third-party plugin. Add `-- <slug> ...` for specific plugins. |
| `npm run env:start` / `env:stop` | Start or stop the development site. |

## Unit tests

Unit tests live in `tests/Unit` and mirror `src/`, for example
`src/WordPress/Hooks.php` is tested by `tests/Unit/WordPress/HooksTest.php` in
the namespace `Cloudflare\APO\Tests\Unit\WordPress`.

WordPress is not loaded:

- WordPress functions are mocked with
  [php-mock](https://github.com/php-mock/php-mock-phpunit) in the namespace of
  the code under test, for example
  `$this->getFunctionMock('Cloudflare\APO\WordPress', 'get_permalink')`.
- The few WordPress classes the code needs (`WP_Post`, `WP_Taxonomy`) are
  stubbed in `tests/Fixtures/WordPressClassStubs.php`, with the same
  constructor signatures as WordPress.

The suite is strict: a test fails when it prints output or when PHP reports a
deprecation.

`composer test:build` copies `tests/Unit` to `build/tests`, prefixes the
vendor namespaces the same way the build does, and runs the copy against the
scoped classes in `build/cloudflare`.

## Integration tests

Integration tests live in `tests/Integration` and run inside the wp-env test
environment, a real WordPress install with the plugin active in
`wp-content/plugins/cloudflare`.

```sh
npm run env:test:start
npm run test:integration
```

The test site is available at http://cloudflare.localhost:8879 while it runs.
The test, build and compatibility sites use a `cloudflare.localhost` address
because the plugin cannot match a site on a plain `localhost` (a host name
without a dot) to a Cloudflare zone. Chrome, Firefox and curl resolve every
`*.localhost` name to your own machine; for other tools, add
`127.0.0.1 cloudflare.localhost` to `/etc/hosts`.

WordPress is loaded the way `wp-admin/admin-ajax.php` loads it, so the
plugin's admin and AJAX hooks are registered and the WordPress admin APIs are
available.

### Writing integration tests

Extend `IntegrationTestCase`. Every test starts with:

- Cloudflare credentials stored for the site's domain. The key and zone ID are
  the documented Cloudflare API examples, not real credentials.
- An `HttpRecorder` in front of all HTTP requests made through the WordPress
  HTTP API. Requests to the Cloudflare API are recorded and answered with the
  responses you register; requests to any other host are blocked, so the suite
  never uses the network.
- `wp_die()` turned into a `WpDieException`, so code that ends a request can be
  tested.

Useful helpers: `createPost()`, `createUser()` (both cleaned up after the
test), `setPluginSetting()`, `respondWithZone()`, `pluginHooks()` (the plugin's
`Hooks` instance) and `hookPriority()`.

```php
$this->http->respondTo('GET', 'zones/' . self::ZONE_ID . '/settings/always_use_https', HttpRecorder::success(array(
    'id' => 'always_use_https',
    'value' => 'off',
)));

// ... run the code under test ...

$requests = $this->http->requestsTo('GET', 'zones/' . self::ZONE_ID . '/settings/always_use_https');
```

Cache purge tests extend `PurgeTestCase`, which switches on APO and answers
every API call a purge makes. `purgedUrls()` returns the URLs sent to
Cloudflare.

Some behaviour needs special handling:

- **wp-config.php constants** such as `CLOUDFLARE_API_KEY` cannot be undefined
  again, so tests that define them run in separate PHP processes
  (`@runTestsInSeparateProcesses`), see `CredentialConstantsTest`.
- **Response headers** cannot be read from inside PHPUnit.
  `ResponseHeadersTest` requests the site over HTTP from the wp-env `wordpress`
  container instead.
- **Features switched on by constants**, such as HTTP/2 server push, are
  switched on per request by the must-use plugin
  `tests/Fixtures/MuPlugins/TestRequestToggles.php`, for example with
  `?cloudflare_test_http2_push=1`.

### Against the build

The build environment mounts `build/` as the plugins folder, so the scoped
plugin is tested exactly as it ships. Its must-use plugin
`tests/Fixtures/MuPlugins/ConflictingPsrLog.php` first loads an unprefixed
`Psr\Log\LoggerInterface` with the return types psr/log 3 added, the way
another plugin bundling psr/log would. The unprefixed source plugin cannot load
next to it; the build must.

```sh
composer build
npm run env:build:start
npm run test:integration:build
```

The build site runs at http://cloudflare.localhost:8877. `ScopedDependenciesTest` only
runs in this environment and is skipped elsewhere. You can rebuild while the
environment is running.

### Other WordPress and PHP versions

The environments use the latest WordPress on PHP 7.4. To test another
combination, set `WP_ENV_CORE` and `WP_ENV_PHP_VERSION` when starting and
running:

```sh
export WP_ENV_CORE=https://wordpress.org/wordpress-6.7.9.zip WP_ENV_PHP_VERSION=8.3
npm run env:test:start
npm run test:integration
```

Start again without the variables to go back to the defaults.

## Browser tests

The browser tests use [Playwright](https://playwright.dev) to open the site in
Chromium, as a visitor or as the administrator, and cover what PHPUnit cannot
see: JavaScript. They live in `tests/E2E` and run against the wp-env test
environment:

```sh
npm run env:test:start
npm run test:e2e
```

| Test | Checks |
|---|---|
| `SettingsPage.spec.js` | Without stored credentials the settings application asks to sign in, and signing in with valid credentials shows the Home tab. An API key Cloudflare rejects shows an error and stores nothing. With credentials, the Home, Settings and Analytics tabs render for the site's zone. Known settings application bugs still happen (see below). |
| `FrontPage.spec.js` | The front page loads for a visitor, a visitor on a phone and a logged-in user, with the right `cf-edge-cache` header. |

Every browser test also fails automatically when:

- the page throws a JavaScript error or logs a console error, for example
  because a script failed to load (in compatibility runs, only errors the
  third-party plugin does not also cause without Cloudflare, see below);
- the plugin made a Cloudflare API call that has no mocked response.

### Known settings application bugs

The settings application (`compiled.js`) is built in the separate
[cloudflare-plugin-frontend](https://github.com/cloudflare/cloudflare-plugin-frontend)
repository, so its bugs cannot be fixed here. Their errors are listed in
`settingsAppBugs` in `tests/E2E/Support/BrowserErrors.js`. The browser tests
show them in the report as "Known settings app bug" instead of failing on them.

Each entry has a test under "Known settings app bugs" in
`SettingsPage.spec.js` that makes the bug happen on purpose. When a new
`compiled.js` fixes the bug, that test fails: remove the test and the entry.

| Bug | Happens when |
|---|---|
| The APO card reads the zone entitlements before they have loaded (`entitlementsNotLoaded`) | The entitlements response arrives after the zone settings. The mock does this on purpose with `delayApiResponses()`. |

### The Cloudflare API mock

A real browser talks to the real site, where the `HttpRecorder` of the
integration tests is not active. The must-use plugin
`tests/Fixtures/MuPlugins/CloudflareApiMock.php` takes its place for web
requests in the test, build and compatibility environments. It answers the
Cloudflare API calls the plugin makes with prepared responses in
`tests/Fixtures/MuPlugins/CloudflareApiMock/*.json` (a zone for the site's
domain, its settings, DNS records and entitlements), so no test ever reaches
the real API. It only accepts the credentials in
`tests/E2E/Support/Environment.js`, and answers anything else the way
Cloudflare does, with "Unknown X-Auth-Key or X-Auth-Email". PHPUnit and
WP-CLI are not affected.

Every call is logged to `wp-content/cloudflare-api-mock.log` in the
environment, marked as mocked or not. When the settings application starts
using a new API call, a browser test fails with the call that is missing: add a
response for it in `CloudflareApiMock::respond()`.

`delayApiResponses({ entitlements: 1500 })` in `Environment.js` makes the mock
answer calls whose path ends in `entitlements` 1.5 seconds later, to change the
order in which the settings application receives its responses.
`resetApiDelays()` removes the delays again.

### Failures

Playwright saves a screenshot and a trace of every failed test in
`tests/E2E/.results`. Open a trace with `npx playwright show-trace <path>` to
step through the test with the page, its console and its network requests. In
CI these are uploaded as an artifact of the failed job.

To run the browser tests against the build or compatibility environment, set
`WP_ENV_CONFIG`, for example
`WP_ENV_CONFIG=.wp-env.compat.json npx playwright test`.

## Compatibility tests

The plugins the Cloudflare plugin is compatible with are listed in
`tests/Integration/Compatibility/Plugins.json`. For each of them,
`scripts/compatibility-tests.sh`:

1. installs the latest version from WordPress.org, if it is not installed yet
   (CI always starts fresh; locally, update installed plugins with
   `npx wp-env --config=.wp-env.compat.json run cli wp plugin update --all`);
2. activates it, together with the plugins it requires;
3. records the browser errors the plugin causes on its own, with Cloudflare
   deactivated (see below);
4. runs the whole integration suite and the browser tests (without the known
   settings app bug tests, which check `compiled.js` itself);
5. deactivates it again.

Every plugin is tested on its own, so a failure points at one plugin.

```sh
npm run env:compat:start
npm run test:compatibility                     # every plugin in the list
npm run test:compatibility -- woocommerce      # only the given slugs
```

The compatibility site runs at http://cloudflare.localhost:8876 with the latest WordPress
on PHP 8.3, because several of the plugins need PHP 8.0 or WordPress 7.0.

Next to the regular integration tests, `CompatibilityTest` only runs here and
checks that, with the other plugin active:

- the plugin under test is actually active;
- the front page renders completely for desktop and mobile visitors and is
  marked cacheable;
- the Cloudflare settings page loads for administrators;
- the settings proxy answers with JSON through a real `admin-ajax.php` request;
- the Cloudflare plugin logged no PHP errors to `wp-content/debug.log`.

Third-party plugins sometimes cause JavaScript or console errors on their
own, for example a script that calls a REST endpoint visitors may not use.
Such errors say nothing about Cloudflare, so before the browser tests the
runner records them: with Cloudflare deactivated,
`tests/E2E/Support/RecordBrowserErrors.js` opens the front page (as a visitor,
on a phone and logged in) and a regular admin page, twice each, and stores the
errors in `tests/E2E/.baseline/<slug>.json`. The browser tests then ignore
those errors, list them in the report as "Also happens without Cloudflare",
and fail on every other error. Errors are compared by their message and the
URL without its query string, as query strings hold nonces and version
numbers. The summary shows how many errors each plugin causes without
Cloudflare. There is no list of known errors to maintain.

These runs use `phpunit-compatibility.xml.dist`: the same as the integration
suite, except that PHP deprecations do not fail a test. Third-party plugins
trigger them in code the tests run, and they say nothing about Cloudflare.
Errors from the Cloudflare plugin itself still fail through the `debug.log`
check.

To add a plugin, add an entry to `Plugins.json`:

```json
{ "slug": "yith-woocommerce-wishlist", "name": "YITH WooCommerce Wishlist", "requires": ["woocommerce"] }
```

`requires` lists the slugs of plugins that must be active first. A plugin that
cannot be tested gets a `skip` entry with the reason, which the runner and CI
report instead of running it. BigCommerce is skipped this way: without a
connected BigCommerce store, it stops every front-end page with an uncaught
exception, with or without Cloudflare.

Only plugins hosted on WordPress.org are supported so far.

## Troubleshooting

- **"The Cloudflare plugin is not active in this WordPress install."** A folder
  the environment mounts was deleted and recreated after it started, usually
  by switching to a branch without `tests/Fixtures/MuPlugins`. Docker keeps
  showing the deleted folder, which is empty. Restart the environment:
  `npm run env:test:stop` and then `npm run env:test:start`.
- **A port is already in use.** The environments use ports 8878 (development),
  8879 (tests) and 8877 (build). Set `WP_ENV_PORT` to use another one.
- **Leftover test data in the compatibility environment.** Reset its
  database with `npx wp-env --config=.wp-env.compat.json reset all`, then
  activate the plugin again with
  `npx wp-env --config=.wp-env.compat.json run cli wp plugin activate cloudflare`.
- **A test failed with a PHP fatal error.** The environments hide PHP errors
  from web requests (`WP_DEBUG_DISPLAY` is off), but the integration suite
  shows them on stderr. Web requests log to `wp-content/debug.log` inside the
  container.

## Coding standards

`phpcs.xml.dist` combines:

- PSR-12, the code style of this plugin: PascalCase classes and file names,
  camelCase methods, four spaces.
- PHPCompatibilityWP, for PHP 7.4 and later.
- The WordPress sniffs that find security and correctness problems:
  escaping output, sanitizing input, nonces, database queries, internationalization
  and a few discouraged functions.

If a finding is a false positive, add a `phpcs:ignore` comment for that sniff
with the reason, for example
`// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a file bundled with the plugin, not a remote URL.`

## Static analysis

PHPStan runs at level 5 on `src`, `deprecated`, the tests and the plugin
entry files, for PHP 7.4.

`phpstan-baseline.neon` is empty and should stay that way. Only add an entry
for an error that cannot be fixed, such as a gap in the WordPress stubs, and
put a `# BASELINE: <reason>` comment above it. On pull requests CI fails when
the baseline grows without such comments.

## Supported versions

- **PHP:** 7.4 and later. The unit tests run on PHP 7.4 to 8.4, with and
  without the intl extension.
- **WordPress:** the five newest major versions. CI reads the current releases
  from the WordPress.org API and runs the integration suite on the latest
  release of each of them with PHP 7.4, and on the newest release with PHP 8.4.

When a new WordPress major version is released, the "Integration Tests"
workflow shows a warning on `readme.txt`. Raise the minimum to the oldest of
the five newest majors in:

- `readme.txt`: `Requires at least`
- `cloudflare.php`: the `Requires at least` header and `CLOUDFLARE_MIN_WP_VERSION`
- `phpcs.xml.dist`: `minimum_supported_wp_version`

## Continuous integration

| Workflow | Jobs |
|---|---|
| Test PHP Source | Composer validation, PHP syntax check and unit tests on PHP 7.4 to 8.4 (with and without intl), PHPCS, and coverage on PHP 8.3 (downloadable as the `coverage` artifact) |
| PHPStan | PHPStan, and on pull requests the baseline check |
| Integration Tests | The integration suite and the browser tests on the five newest WordPress majors |
| Test Build Artifact | Build with PHP-Scoper, then the syntax check, the unit tests, the integration suite and the browser tests against the build |
| Compatibility Tests | The integration suite and the browser tests next to each plugin in `Plugins.json`, one job per plugin. Also runs weekly, as the plugins release independently. |
