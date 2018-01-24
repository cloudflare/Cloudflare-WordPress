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

[WordPress](https://github.com/cloudflare/Cloudflare-WordPress), [CPanel](https://github.com/cloudflare/CloudFlare-CPanel), [Magento](https://github.com/cloudflare/CloudFlare-Magento) are the main repositories of the plugins. Every plugin has a config.js file which allows them to control the frontend of the plugin. 

Below are Cloudflare maintained repositories the plugins depend on. 

* [cloudflare-frontend](https://github.com/cloudflare/CloudFlare-FrontEnd) is a generic frontend used in plugins. You can add/remove cards simply by editing [config](https://github.com/cloudflare/CloudFlare-FrontEnd/blob/master/config.js) file.
* [cf-ui](https://github.com/cloudflare/cf-ui) is a Cloudflare UI Framework where cloudflare-frontend is using. 
* [cloudflare-plugin-backend](https://github.com/cloudflare/cloudflare-plugin-backend) is a generic backend plugins use.
* [cf-ip-rewrite](https://github.com/cloudflare/cf-ip-rewrite) allows to rewrite Cloudflare IP's in Application level. 
* [mod_cloudflare](https://github.com/cloudflare/mod_cloudflare) allows Apache to rewrite Cloudflare IP's with user IP's. It is not used in plugins itself but it maybe be a better alternative then `cf-ip-rewrite`.

### Dependency Graph

![](https://i.imgur.com/oXEKYVd.png)

## WordPress Plugin Specific Details

Cloudflare WordPress Plugin uses PHPUnit for testing. WordPress specific function are mocked inside [WordPressWrapper](https://github.com/cloudflare/Cloudflare-WordPress/blob/master/src/WordPress/WordPressWrapper.php) class. Everything under `src/` directory should have unit test written.

## Frontend Updates

Each plugin may use different Frontend [versions]((https://github.com/cloudflare/CloudFlare-FrontEnd/releases)). When publishing a Frontend release we copy the following files to other plugins;

* `assets/`
* `fonts/`
* `lang/`
* `stylesheets/`
* `compiled.js` which is created when `gulp compress` command is called within Frontend repository.

## Translations

The plugins use a common language file which is located [here](https://github.com/cloudflare/CloudFlare-FrontEnd/tree/master/lang). English translation is always up to date where as other translations are not. If you have any issues or questions regarding with translations feel free to open an [issue](https://github.com/cloudflare/CloudFlare-FrontEnd/issues).
