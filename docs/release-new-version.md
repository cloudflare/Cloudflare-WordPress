# Releasing New Version Of Cloudflare WordPress Plugin

### Update any backend/frontend dependencies

If there are any required changes from the backend, frontend or dependant
projects, update them and commit the changes.

### Update readme.txt and plugin version references

WordPress uses the readme.txt heavily for metadata about the plugin. You will
need to update `== Changelog ==` section according to what code changes have
been made since the last release.

To bump all the places where the plugin version is defined, run
`scripts/bump-plugin-version.sh x.x.x` (replacing x.x.x) with your proposed
version number.

Now, update the composer checksum file using `composer update --no-dev`.

Commit all the changes you've made to this point and push up a pull request.

## Prepare to release

Ensure all desired changes are merged into master from their feature and bugfix
branches. Ensure that CI is all passing. If it is not, do not create a new
release -- fix any failures or violations.

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
