# Releasing New Version Of Cloudflare WordPress Plugin

## Overview
Releasing updates to the Cloudflare WordPress plugin happens in 3 major phases: 

1. Publishing [a release on GitHub](https://github.com/cloudflare/Cloudflare-WordPress/releases)
2. Cloning this same release locally by referencing its tag. 
3. Prepping it locally and adding it to SVN so as to make the release available in [the official WordPress.org plugin repo](https://en-gb.wordpress.org/plugins/cloudflare/). 

The steps should be done in this exact order. Publishing the release on GitHub allows everyone to see the current state of the plugin. However, users who manage their plugins from within their WordPress installations will only be able to update their plugins after the release has been pushed to the official WordPress SVN repo. 

## Prepare to release 

1. Ensure all desired changes are merged into master from their feature and bugfix branches. 
2. Ensure that all tests pass by running ```composer test``` from within your Cloudflare plugin's working directory 
3. Ensure the linter passes by running ```composer format``` from within your Cloudflare plugin's working directory 

### 1. Release new version to GitHub

1. Change directory to project root folder. Ensure all the preparation steps above are complete. Check out the master branch and ensure it is up to date
2.  Edit the change logs in the `readme.txt` file. Follow the exact pattern of previous releases
3.  Run `./scripts/deployment/publish_github.py`
4.  Open `https://github.com/cloudflare/CloudFlare-WordPress/releases`. You should see the tag you just pushed by running the script as the latest release, but it will not contain any Markdown notes yet.
5.  Edit this release in the GitHub UI by clicking the 3 dot menu to the right of the release and paste the contents of the readme.txt changelog entry that you added for this release. Look at previous release examples for guidance. The changelog notes on the GitHub releases match the readme.txt changelog entries.
6. Click **Draft a new release**. Enter the tag associated with this release. Save.

### 2. Locally prepare the code for release to the official WordPress.org SVN repo

1. Call `git clone --branch LASTEST_TAG https://github.com/cloudflare/CloudFlare-WordPress ~/Desktop/wordpress_before_release`, where LATEST_TAG is the exact GitHub release tag you just created, and  `~/Desktop/wordpress_before_release` is an optional path on your local machine
- Run `~/Desktop/wordpress_before_release/scripts/deployment/trim_code_for_release.sh ~/Desktop/wordpress_before_release`, replacing the path as necessary - this utility script removes all unnecessary folders and files, making the directory suitable for publishing to SVN / WordPress
2. Manually copy over config.json and composer.json. This is required in [PluginActions.php](https://github.com/cloudflare/Cloudflare-WordPress/blob/master/src/WordPress/PluginActions.php#L215)

    `cp ~/Desktop/wordpress_before_release/config.json ~/Desktop/wordpress_before_release/src/Wordpress/config.json`
     
    `cp ~/Desktop/wordpress_before_release/composer.json ~/Desktop/wordpress_before_release/src/Wordpress/composer.json`

### 3. SVN Release

1. Ensure all previous steps have been completed successfully
2. If you do not already have access to the SVN repo for the official Cloudflare WordPress plugin, you need to request it internally from someone who does
3. If you do not already have the SVN repo checked out locally, check it out: ```svn checkout https://plugins.svn.wordpress.org/cloudflare/```
4. **Replace** the `SVN_PATH/trunk` folder with `wordpress_before_release` folder, using your custom path as necessary 
5. Create a new tag folder in `SVN_PATH/tags` with the `LASTEST_TAG` tag name, where LATEST_TAG matches the GitHub release tag you just created. Note that in GitHub we use `vX.Y.Z` tagging format where as in SVN we use `X.Y.Z` format.
6. Copy the files in `SVN_PATH/trunk` to `SVN_PATH/tags/LASTEST_TAG` folder
7. Add the modified files to SVN and commit with the message `Tagging version X.Y.Z`

### 4. Verify 

The WordPress.org plugin SVN repo should update automatically, and you should see the latest tag reflected on [the official WordPress Cloudflare plugin page shortly](https://en-gb.wordpress.org/plugins/cloudflare/).

At this point, users should see a notification on their plugins page that the Cloudflare plugin has a newer version available and should be able to update it from within WordPress. 

As a sanity check, use a working WordPress instance to install and smoke test the latest version of the plugin.
