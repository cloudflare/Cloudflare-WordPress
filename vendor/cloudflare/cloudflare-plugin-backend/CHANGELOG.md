# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.1.13](#1.1.13) - 2017-02-28
### Added 
- Travis CI [#34](https://github.com/cloudflare/cloudflare-plugin-backend/pull/34)

### Fixed
- Fixed Guzzle type hints on getErrorMessage() [#33](https://github.com/cloudflare/cloudflare-plugin-backend/pull/33)

## [1.1.12](#1.1.12) - 2016-02-3
### Changed
- Moved Guzzle to require-dev [#31](https://github.com/cloudflare/cloudflare-plugin-backend/pull/31)

## [1.1.11](#1.1.11) - 2016-09-27
### Fixed
- Fixed bug where requests were not paginating. [#27](https://github.com/cloudflare/cloudflare-plugin-backend/pull/27)

## [1.1.10](#1.1.10) - 2016-09-12
### Fixed
- Fixed method PUT not having a http body bug. [#26](https://github.com/cloudflare/cloudflare-plugin-backend/pull/26)

## [1.1.9](#1.1.9) - 2016-09-12
### Fixed
- Fixed bugs in Guzzle3 and backend which didn't work with PHP 5.3. [#25](https://github.com/cloudflare/cloudflare-plugin-backend/pull/25)

## [1.1.8](#1.1.8) - 2016-09-12
### Changed
- Downgraded guzzlehttp 5.0 to guzzle 3.9 to support PHP 5.3. [#23](https://github.com/cloudflare/cloudflare-plugin-backend/pull/23)

## [1.1.7](#1.1.7) - 2016-09-6
### Changed
- Moved plugin settings consts from DataStore to Plugin API. [#18](https://github.com/cloudflare/cloudflare-plugin-backend/pull/18)
- createPluginSettingObject() is no longer static. [#18](https://github.com/cloudflare/cloudflare-plugin-backend/pull/18)

### Fixed
- Fixed patchPluginSettings() to return correct JSON structure. [#19](https://github.com/cloudflare/cloudflare-plugin-backend/pull/19)
- Fixed bug where getPluginSettings was returning incorrect JSON structure. [#18](https://github.com/cloudflare/cloudflare-plugin-backend/pull/18)


## [1.1.6](#1.1.6) - 2016-09-5
### Fixed
- Fixed where DataStoreInterface was not included [c3502d](https://github.com/cloudflare/cloudflare-plugin-backend/commit/c3502db2904be385e2ad0e37287085fcecbfba5f)

## [1.1.5](#1.1.5) - 2016-09-2
### Fixed
- Fixed Datastore::get [bdfb9](https://github.com/cloudflare/cloudflare-plugin-backend/commit/bdfb94275bb297473cf0801b33938810c32f0cc3)

## [1.1.4](#1.1.4) - 2016-09-2
### Changed
- Changed Datastore to support objects. [#15](https://github.com/cloudflare/cloudflare-plugin-backend/pull/15)
- Made PageRuleLimitException shorter. [#16](https://github.com/cloudflare/cloudflare-plugin-backend/pull/16)

## [1.1.3](#1.1.3) - 2016-08-31
### Fixed
- Fixed bug where CF\API\AbstractPluginActions::login() would log the user in with invalid credentials. [#14](https://github.com/cloudflare/cloudflare-plugin-backend/pull/14)

## [1.1.2](#1.1.2) - 2016-08-17
### Changed
- Fixed bug in CF\API\Exception. [#13](https://github.com/cloudflare/cloudflare-plugin-backend/pull/13)

## [1.1.1](#1.1.1) - 2016-08-16
### Added
- Added plugin_specific_cache_tag setting to CF\API\Plugin settings. [#12](https://github.com/cloudflare/cloudflare-plugin-backend/pull/12)

## [1.1.0](#1.1.0) - 2016-08-11
### Added
- Added CF\Router\RequestRouter to consolidate duplicate request routing logic each plugin was implementing. [#8](https://github.com/cloudflare/cloudflare-plugin-backend/pull/8)

### Changed
- PluginRoutes, PluginActions moved to CF\API to consolidate the Internal Plugin API logic across all plugins. [#10](https://github.com/cloudflare/cloudflare-plugin-backend/pull/10)

## [1.0.9](#1.0.9) - 2016-08-03
### Changed
- Removed static type checking to support earlier php versions [ad13c1e](https://github.com/cloudflare/cloudflare-plugin-backend/commit/ad13c1ec6edeceae5a85f8912208ce2c80f4a5f2)

## [1.0.8](#1.0.8) - 2016-08-02
### Changed
- Fixed error message bug [61584ca](https://github.com/cloudflare/cloudflare-plugin-backend/commit/61584ca56f8ed6ba76cb321593955e0b57f3c88d)

## [1.0.7](#1.0.7) - 2016-08-02
### Changed
- Updated CHANGELOG [5e72177](https://github.com/cloudflare/cloudflare-plugin-backend/commit/5e72177aadf1c34cf75904b52bf017e7b6c6c672)

## [1.0.6](#1.0.6) - 2016-08-02
### Changed
- Changed error message from always "Bad Request" to original API message [235b020](https://github.com/cloudflare/cloudflare-plugin-backend/commit/235b020ad48cf9c0d2cdcb067b34d1424f0571f6)

## [1.0.5](#1.0.5) - 2016-07-22
### Added
- PI-697 added PLUGIN_SPECIFIC_CACHE consts to CF\API\Plugin [10fb134](https://github.com/cloudflare/cloudflare-plugin-backend/commit/10fb1346d81e6b7fb71abfdfb93ce12c3d55fb91)

## [1.0.4](#1.0.4) - 2016-07-15
### Added
- Added setting name consts to CF\API\Plugin [70372ab](https://github.com/cloudflare/cloudflare-plugin-backend/commit/70372ab0d1e294e0e6b57799e31c8a22ed4dedf6)


## [1.0.3](#1.0.3) - 2016-06-27
### Added
- Added CF\API\Plugin.php built by @thellimist to handle plugin specific API calls. [#3](https://github.com/cloudflare/cloudflare-plugin-backend/pull/3)

## [1.0.2](#1.0.2) - 2016-06-14
### Changed
- CF\Integration\LoggerInterface::logAPICall() moved to CF\API\AbstractAPIClient::logAPICall(). [#2](https://github.com/cloudflare/cloudflare-plugin-backend/pull/2)

## Removed
- Removed CF\Integration\LoggerInterface. [#2](https://github.com/cloudflare/cloudflare-plugin-backend/pull/2)

## [1.0.1](#1.0.1) - 2016-06-07
### Changed
- CF\Integration\LoggerInterface now implements PSR-3 LoggerInterface. [#1](https://github.com/cloudflare/cloudflare-plugin-backend/pull/1)
