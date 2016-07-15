# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
