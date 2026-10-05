# Developer tooling

## [PsySH](https://psysh.org/)

At some point in debugging you have probably wrote something like this to get a
quick snippet working.

```php
<?php

require_once "vendor/autoload.php"

use ...

// do a few lines of actual work here
```

While this works, it's annoying to do over and over again. Instead, we recommend
installing `psysh` and have a fully loaded shell where you can play with
snippets without the overhead of managing these files. Install is as straight
forward as `composer g require psy/psysh:@stable`. There is also a bunch of
other cool features to take advantage of; check out the website for more details!

This isn't included in the development dependencies due to too many conflicts
with other packages.

## wp-env

[wp-env](https://www.npmjs.com/package/@wordpress/env) runs WordPress in Docker
from the configuration files in the root of the repository. It is installed by
`npm install`. There are four environments, each with its own database:

| Environment | Config | Site | Start | Used for |
|---|---|---|---|---|
| Development | `.wp-env.json` | http://localhost:8878 | `npm run env:start` | Trying out changes |
| Tests | `.wp-env.test.json` | http://cloudflare.localhost:8879 | `npm run env:test:start` | `npm run test:integration`, `npm run test:e2e` |
| Build | `.wp-env.build.json` | http://cloudflare.localhost:8877 | `npm run env:build:start` | `npm run test:integration:build`, `npm run test:e2e:build` |
| Compatibility | `.wp-env.compat.json` | http://cloudflare.localhost:8876 | `npm run env:compat:start` | `npm run test:compatibility` |

The development and test environments load the plugin from this checkout, the
build environment loads `build/cloudflare` (run `composer build` first). All of
them use the latest WordPress and activate the plugin after starting. They run
PHP 7.4, except the compatibility environment, which runs PHP 8.3 because
several of the third-party plugins it tests need PHP 8.0. Log in at `/wp-admin` with `admin` / `password`.
The test, build and compatibility sites use `cloudflare.localhost` instead of
`localhost`; [testing.md](testing.md#integration-tests) explains why.

Stop an environment with the matching `stop` script, for example
`npm run env:test:stop`. Run WP-CLI in one with `npx wp-env run cli wp ...`,
adding `--config=` with the environment's config file for the other
environments, for example `--config=.wp-env.test.json`.

If a folder the environment mounts is deleted and recreated while it runs, for
example by switching branches, the container keeps seeing the deleted, empty
folder. Stop and start the environment again. See
[testing.md](testing.md#troubleshooting) for this and other problems.

## Docker

The Docker Compose setup below predates wp-env. It is still useful for step
debugging with Xdebug and for inspecting HTTP traffic with MITMProxy; use wp-env
for running the tests.

To make the development environment somewhat reproducible, we ship a Docker
Compose configuration file in the root of the repository. A simple
`docker compose up -d` at the root of the repository will stand up MySQL,
WordPress running on Apache, MITMProxy and Adminer containers.

Port mapping is as follows:

- MySQL: `3306`
  - Adminer UI: `9010`
- WordPress: `9999`
- MITMProxy:
  - Web UI: `9080` (only accessible from 127.0.0.1, not a domain)
  - Listener: `9081`
- XDebug:
  - Incoming client connections from `9003`

## XDebug

XDebug is a profiler and debugger for PHP. The container has it built in by
default and all you need to do is have a listener setup on your host machine
matching the ports above and it will be available. We use XDebug 3.

Example Visual Studio code launch configuration

```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "hostname": "localhost",
            "pathMappings": {
                "/var/www/html/wp-content/plugins/cloudflare": "${workspaceFolder}",
            },
            "xdebugSettings": {
                "max_data": 65535,
                "show_hidden": 1,
                "max_children": 100,
                "max_depth": 5
            }
        }
    ]
}
```

If you would like more advanced features such as gcstats, profiling or tracing,
you can toggle them on within the Dockerfile.wordpress by uncommenting the `sed`
replacement and include your modes to enable.

Outputs are generated to the `xdebug` directory in this repository.

## MITM Proxy

MITMProxy is a tool that allows you to inspect and intercept HTTP traffic. This
is useful if you are making HTTP requests/responses and you want to watch the
transactions in various forms instead of dumping the various components.

You can also use this from your host machine as the ports are exposed.

```
curl -x 127.0.0.1:9081 http://example.com
```

The web UI lives at 127.0.0.1:9080.

To use a MITM proxy with WordPress or Guzzle, you will need to ignore SSL
verification otherwise you will recieve certificate errors. Once you update your
code to ignore certificates, you can uncomment the `HTTP_PROXY`/`HTTPS_PROXY`
environment variables in the Docker Compose file to enable it.
