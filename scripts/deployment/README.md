# Releasing New Version Of Cloudflare WordPress Plugin

## How it works

1. Run script for [releasing new GitHub version](#releasing-new-github-version)
2. Run script for [triming code before release](#triming-code-before-release)
3. Manually do [SVN Release](#svn-release)

### Releasing New GitHub Version

- Change directory to project root folder
- Edit `readme.txt` Change Logs
- Run `./scripts/deployment/publish_github.py`
- Open `https://github.com/cloudflare/CloudFlare-WordPress`
- Edit GitHub Change Logs
- Draft a new Release

### Triming Code Before Release

- Call `git clone --branch LASTEST_TAG https://github.com/cloudflare/CloudFlare-WordPress ~/Desktop/wordpress_before_release`
  - Note: `~/Desktop/wordpress_before_release` is an optional path.
- Run `~/Desktop/wordpress_before_release/scripts/deployment/trim_code_for_release.sh ~/Desktop/wordpress_before_release`

### SVN Release

- After trimming **replace** the `SVN_PATH/trunk` folder with `wordpress_before_release` folder
- Create a new tag folder in `SVN_PATH/tags` with the `LASTEST_TAG` tag name.
  - Note: In GitHub we use `vX.Y.Z` tagging format where as in SVN we use `X.Y.Z` format.
- Copy the files in `SVN_PATH/trunk` to `SVN_PATH/tags/LASTEST_TAG` folder
- Add the modified files to SVN and commit with the message `Tagging version X.Y.Z`.
