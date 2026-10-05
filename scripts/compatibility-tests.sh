#!/bin/bash
#
# Runs the integration suite once per third-party plugin listed in
# tests/Integration/Compatibility/Plugins.json, each time with only that plugin
# (and the plugins it requires) active next to Cloudflare.
#
# Needs the compatibility environment: npm run env:compat:start
#
# Usage:
#   scripts/compatibility-tests.sh              # every plugin in the list
#   scripts/compatibility-tests.sh woocommerce  # only the given slugs

set -uo pipefail

CONFIG=".wp-env.compat.json"
LIST="tests/Integration/Compatibility/Plugins.json"
RESULTS=()
FAILED=0

# wp-env reads stdin, which would swallow the plugin list the loop reads.
wp() {
    npx wp-env --config="$CONFIG" run cli wp "$@" 2>/dev/null < /dev/null
}

# "slug|name|space separated required slugs|skip reason" per line. The
# separator must not be whitespace: read merges adjacent whitespace separators,
# which would drop an empty field.
plugins() {
    node -e '
        const list = require(require("path").resolve(process.argv[1]));
        const only = process.argv.slice(2);
        for (const p of list) {
            if (only.length === 0 || only.includes(p.slug)) {
                console.log([p.slug, p.name, p.requires.join(" "), p.skip || ""].join("|"));
            }
        }
    ' "$LIST" "$@"
}

while IFS='|' read -r slug name requires skip; do
    echo
    echo "==> ${name} (${slug})"

    if [ -n "$skip" ]; then
        echo "Skipped: ${skip}"
        RESULTS+=("SKIP  ${name}: ${skip}")
        continue
    fi

    set -- $requires "$slug"

    for plugin in "$@"; do
        wp plugin is-installed "$plugin" || wp plugin install "$plugin" --quiet || true
    done

    if ! activation=$(wp plugin activate "$@" 2>&1); then
        if grep -qi "requires" <<< "$activation"; then
            echo "Skipped: ${activation}"
            RESULTS+=("SKIP  ${name}: requirements not met")
        else
            echo "${activation}"
            RESULTS+=("FAIL  ${name}: could not be activated")
            FAILED=1
        fi
        wp plugin deactivate "$@" --quiet || true
        continue
    fi

    # Plugins such as WooCommerce redirect the first admin request after
    # activation to their setup wizard.
    wp transient delete --all --quiet || true
    wp option update cloudflare_test_compatibility_plugin "$slug" --quiet

    # Browser errors the plugin causes on its own: record them with Cloudflare
    # deactivated, so the browser tests only fail on errors Cloudflare adds.
    baseline="tests/E2E/.baseline/${slug}.json"
    wp plugin deactivate cloudflare --quiet
    if ! WP_ENV_CONFIG="$CONFIG" node tests/E2E/Support/RecordBrowserErrors.js "$baseline" < /dev/null; then
        echo "Could not record the browser errors without Cloudflare."
        rm -f "$baseline"
    fi
    wp plugin activate cloudflare --quiet

    result="PASS"

    if ! phpunit=$(npx wp-env --config="$CONFIG" run cli --env-cwd=wp-content/plugins/cloudflare \
        php vendor/bin/phpunit -c phpunit-compatibility.xml.dist 2>&1 < /dev/null); then
        echo "$phpunit" | grep -vE '^(ℹ|✔|✖)'
        result="FAIL"
    fi

    # Browser tests (Playwright) against the same site. The known settings app
    # bug tests check compiled.js itself, not how it works next to a plugin.
    if ! browser=$(BROWSER_ERROR_BASELINE="$baseline" WP_ENV_CONFIG="$CONFIG" \
        npx playwright test --grep-invert "Known settings app bugs" 2>&1 < /dev/null); then
        echo "$browser"
        result="FAIL"
    fi

    [ "$result" = "FAIL" ] && FAILED=1
    known=$(node -e 'try { console.log(require(require("path").resolve(process.argv[1])).length) } catch (e) { console.log(0) }' "$baseline")
    RESULTS+=("${result}  ${name}: PHPUnit $(grep -E '^(OK|Tests:)' <<< "$phpunit" | tail -1 | sed 's/^OK, but incomplete, skipped, or risky tests!$//') | browser $(grep -oE '[0-9]+ (passed|failed|flaky|skipped)' <<< "$browser" | awk '!seen[$0]++' | tr '\n' ' ')| ${known} browser error(s) also without Cloudflare")

    wp option delete cloudflare_test_compatibility_plugin --quiet || true
    wp plugin deactivate "$slug" $requires --quiet || true
done < <(plugins "$@")

echo
echo "==> Summary"
printf '%s\n' "${RESULTS[@]}"

exit "$FAILED"
