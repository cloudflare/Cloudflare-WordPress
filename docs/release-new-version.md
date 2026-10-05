# Releasing New Version Of Cloudflare WordPress Plugin

### Update any backend/frontend dependencies

If there are any required changes from the backend, frontend or dependant
projects, update them and commit the changes.

### Check the supported WordPress versions

The plugin supports the five newest WordPress major versions. If the latest
"Integration Tests" run shows a warning on `readme.txt`, a new major has been
released: raise the minimum as described in
[testing.md](testing.md#supported-versions).

Set `Tested up to` in `readme.txt` to the newest WordPress release the
"Integration Tests" workflow ran against.

### Update readme.txt and plugin version references

WordPress uses the readme.txt heavily for metadata about the plugin. You will
need to update `== Changelog ==` section according to what code changes have
been made since the last release.

To bump all the places where the plugin version is defined, run
`scripts/bump-plugin-version.sh x.x.x` (replacing x.x.x) with your proposed
version number.

Now, update the checksum in composer.lock using `composer update --lock`. This
only refreshes the content-hash for the new version; it does not upgrade any
packages.

Commit all the changes you've made to this point and push up a pull request.

## Prepare to release

Ensure all desired changes are merged into master from their feature and bugfix
branches. Ensure that CI is all passing. If it is not, do not create a new
release -- fix any failures or violations.

### Test the release zip (dry run)

The "Release plugin" workflow can build the zip WordPress.org would get without
deploying it. Run it manually from the Actions tab ("Run workflow"), or from
the command line:

```sh
gh workflow run release.yml --ref <branch>
```

Manual runs are always dry runs, and so is every run in a fork, including a
published release there. Only a published release in
`cloudflare/Cloudflare-WordPress` deploys to WordPress.org.

A dry run:

- builds the plugin;
- copies the files the same way a real release does, using `.distignore`;
- skips the SVN commit;
- uploads `cloudflare.zip` as an artifact of the run.

Install that zip on a test site to smoke test the release before publishing it.

### Create a new GitHub release

1. Open `https://github.com/cloudflare/CloudFlare-WordPress/releases`.
1. Create a new release with the next semantically correct version. Fill in any
   CHANGELOG, notes or upgrade entries from the readme.txt file.
1. Click save.

By creating a new published release (not draft), it will trigger a GitHub Action
to bundle the required files to generate the SVN changes and push them to
wordpress.org

### Verify

The WordPress.org plugin SVN repo should update automatically, and you should
see the latest tag reflected on [the official WordPress Cloudflare plugin page shortly](https://en-gb.wordpress.org/plugins/cloudflare/).

At this point, users should see a notification on their plugins page that the
Cloudflare plugin has a newer version available and should be able to update it
from within WordPress.

As a sanity check, use a working WordPress instance to install and smoke test
the latest version of the plugin.
