#!/usr/bin/env python

import json
import subprocess
import sys
from collections import OrderedDict

COMPOSER_FILE_NAME = "composer.json"
README_FILE_NAME = "readme.txt"
CONFIG_FILE_NAME = "config.json"
CLOUDFLARE_PHP_FILE_NAME = "cloudflare.php"
README_TXT_STABLE_TAG_LINE_NUMBER = 6
CLOUDFLARE_PHP_VERSION_LINE_NUMBER = 6
GIT_REPOSITORY_RELEASES = "https://github.com/cloudflare/Cloudflare-WordPress/releases"


def print_shiny(printable):
    print('==========================================')
    print()
    print(printable)
    print()
    print('==========================================')


def run_composer_test():
    subprocess.check_output(['composer', 'test'])


def read_version():
    with open(COMPOSER_FILE_NAME, 'r+') as f:
        data = json.load(f)
        version = data['version']
        print("Current version: " + version)
        return version


def ask_for_new_version(version):
    version_splited = version.split(".")
    v_major = int(version_splited[0])
    v_minor = int(version_splited[1])
    v_patch = int(version_splited[2])

    v_minor = v_minor + 1
    v_patch = 0
    suggested_version = "%d.%d.%d" % (v_major, v_minor, v_patch)

    new_version = input("Enter a version number [%s]: " % suggested_version)
    if new_version == "":
        new_version = suggested_version

    return new_version


def update_version_number_in_composer_json(new_version):
    with open(COMPOSER_FILE_NAME, 'r+') as f:
        data = json.load(f, object_pairs_hook=OrderedDict)
        data['version'] = new_version
        f.seek(0)          # should reset file position to the beginning.
        json.dump(data, f, indent=4)
        return 1

    return 0


def update_version_number_in_config_json(new_version):
    with open(CONFIG_FILE_NAME, 'r+') as f:
        data = json.load(f, object_pairs_hook=OrderedDict)
        data['version'] = new_version
        f.seek(0)          # should reset file position to the beginning.
        json.dump(data, f, indent=4)
        return 1

    return 0


def update_version_number_in_readme_txt(new_version):
    lines = None
    with open(README_FILE_NAME, 'r') as f:
        lines = f.readlines()

        if "Stable tag:" not in lines[README_TXT_STABLE_TAG_LINE_NUMBER - 1]:
            print_shiny("readme.txt file doesn't have stable tag in the correct line number. Please check the code")
            sys.exit()

        lines[README_TXT_STABLE_TAG_LINE_NUMBER - 1] = "Stable tag: " + new_version + "\n"

    with open(README_FILE_NAME, 'w') as f:
        f.writelines(lines)
        return 1

    return 0


def update_version_number_in_cloudflare_php(new_version):
    lines = None
    with open(CLOUDFLARE_PHP_FILE_NAME, 'r') as f:
        lines = f.readlines()

        if "Version:" not in lines[CLOUDFLARE_PHP_VERSION_LINE_NUMBER - 1]:
            print_shiny("readme.txt file doesn't have stable tag in the correct line number. Please check the code")
            sys.exit()

        lines[CLOUDFLARE_PHP_VERSION_LINE_NUMBER - 1] = "Version: " + new_version + "\n"

    with open(CLOUDFLARE_PHP_FILE_NAME, 'w') as f:
        f.writelines(lines)
        return 1

    return 0


def set_version(new_version):
    print("Will set new version to be %s" % new_version)
    number_of_files_updated = 0
    number_of_files_updated += update_version_number_in_composer_json(new_version)
    number_of_files_updated += update_version_number_in_readme_txt(new_version)
    number_of_files_updated += update_version_number_in_cloudflare_php(new_version)
    number_of_files_updated += update_version_number_in_config_json(new_version)
    if number_of_files_updated != 4:
        print("Failed updating version numbers")
        sys.exit()

    print("Version numbers updated to %s" % new_version)


def git_commit_and_push(new_version):
    subprocess.check_output(['git', 'add', COMPOSER_FILE_NAME])
    subprocess.check_output(['git', 'add', README_FILE_NAME])
    subprocess.check_output(['git', 'add', CLOUDFLARE_PHP_FILE_NAME])
    subprocess.check_output(['git', 'add', CONFIG_FILE_NAME])
    subprocess.check_output(['git', 'commit', '-m', "Version bump to %s" % new_version])
    subprocess.check_output(['git', 'tag', '-a', "v%s" % new_version, '-m', "'Tagging version %s'" % new_version])
    subprocess.check_output(['git', 'push', 'origin', '--tags'])
    subprocess.check_output(['git', 'push', 'origin'])


def main():
    # Run command "composer test"
    run_composer_test()

    # Read the current version
    version = read_version()

    # Ask for new version
    new_version = ask_for_new_version(version)

    # Modify files with the new version
    set_version(new_version)

    # Tag git version, commit and push
    git_commit_and_push(new_version)

    # Notice user
    print_shiny("Please do not forget to edit changelog.\n" +
                GIT_REPOSITORY_RELEASES + "\n\n")

if __name__ == "__main__":
    main()
