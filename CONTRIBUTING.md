# Contributing to Cloudflare Plugins

👍🎉 First off, thanks for taking the time to contribute! 🎉👍

## How To Contribute

We welcome community contribution to this repository. To help add functionality or address issues, please take the following steps:

* Fork the repository from the master branch.
* Create a new branch for your features / fixes.
* Make the changes you wish to see.
* Add tests for all changes.
* Create a pull request with details of what changes have been made, explanation of new behaviour, and link to issue that is addressed.
* Addressing (with @...) one or more of the maintainers in the description of the pull request
* Ensure documentation contains the correct information.
* Pull requests will be reviewed and hopefully merged into a release.

## Before Contributing

Cloudflare has multiple plugins using shared codebases. 

[WordPress](https://github.com/cloudflare/Cloudflare-WordPress), [CPanel](https://github.com/cloudflare/CloudFlare-CPanel), [Magento](https://github.com/cloudflare/CloudFlare-Magento) are the main repositories of the plugins. Every plugin has a config.json file which allows them to control the frontend of the plugin.

Below are Cloudflare maintained repositories the plugins depend on. 

* [cloudflare-frontend](https://github.com/cloudflare/CloudFlare-FrontEnd) is a generic frontend used in plugins. You can add/remove cards simply by editing [config](https://github.com/cloudflare/cloudflare-plugin-frontend/blob/master/config.json.sample) file.
* [cf-ui](https://github.com/cloudflare/cf-ui) is a Cloudflare UI Framework where cloudflare-frontend is using. 
* [cloudflare-plugin-backend](https://github.com/cloudflare/cloudflare-plugin-backend) is a generic backend plugins use.
* [cf-ip-rewrite](https://github.com/cloudflare/cf-ip-rewrite) allows to rewrite Cloudflare IP's in Application level. 
* [mod_cloudflare](https://github.com/cloudflare/mod_cloudflare) allows Apache to rewrite Cloudflare IP's with user IP's. It is not used in plugins itself but it maybe be a better alternative then `cf-ip-rewrite`.

### Dependency Graph

![](https://i.imgur.com/oXEKYVd.png)

## WordPress Plugin Specific Details

### Development setup

You need PHP 7.4 or later with Composer 2 for the unit tests and static checks, and Node.js 20 or later with Docker for the integration and browser tests.

```sh
composer install
npm install
```

Before you open a pull request, run the checks CI enforces:

```sh
composer qa                 # PHPCS, PHPStan and the unit tests
npm run env:test:start      # a WordPress test site in Docker (wp-env)
npm run test:integration    # the integration tests on that site
npm run test:e2e            # the browser tests on that site (needs: npx playwright install chromium)
```

### Tests

* Code in `src/` should be covered by unit tests in `tests/Unit`, which mirrors the `src/` folders. WordPress is not loaded there: WordPress functions are mocked with php-mock.
* Behaviour that depends on WordPress itself, such as hooks, options, AJAX requests or cache purges, should be covered by integration tests in `tests/Integration`, which run against a real WordPress install.

[docs/testing.md](docs/testing.md) describes every suite, the test helpers and the supported PHP and WordPress versions. [docs/developer-tools.md](docs/developer-tools.md) describes the local environments.

### Coding standards

The plugin follows PSR-12, checked by `composer lint` together with PHP compatibility and the WordPress security sniffs. `composer stan` runs PHPStan. Do not add entries to `phpstan-baseline.neon` without a `# BASELINE:` comment explaining why the error cannot be fixed.

## Frontend Updates

Each plugin may use different Frontend [versions]((https://github.com/cloudflare/CloudFlare-FrontEnd/releases)). When publishing a Frontend release we copy the following files to other plugins;

* `assets/`
* `fonts/`
* `lang/`
* `stylesheets/`
* `compiled.js` which is created when `gulp compress` command is called within Frontend repository.

## Translations

The plugins use a common language file which is located [here](https://github.com/cloudflare/CloudFlare-FrontEnd/tree/master/lang). English translation is always up to date where as other translations are not. If you have any issues or questions regarding with translations feel free to open an [issue](https://github.com/cloudflare/CloudFlare-FrontEnd/issues).
