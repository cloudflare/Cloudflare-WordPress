<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\WordPress\DataStore;

/**
 * The Settings > Cloudflare page and the link to it on the Plugins screen.
 */
class AdminPageTest extends IntegrationTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['submenu']['options-general.php']);

        parent::tearDown();
    }

    public function testSettingsPageIsAddedForAdministrators()
    {
        wp_set_current_user($this->createUser('administrator'));

        $this->pluginHooks()->cloudflareConfigPage();

        $this->assertContains('cloudflare', $this->settingsMenuSlugs());
    }

    public function testSettingsPageIsNotAddedForEditors()
    {
        wp_set_current_user($this->createUser('editor'));

        $this->pluginHooks()->cloudflareConfigPage();

        $this->assertNotContains('cloudflare', $this->settingsMenuSlugs());
    }

    public function testPluginsScreenLinksToTheSettingsPage()
    {
        $links = apply_filters('plugin_action_links_cloudflare/cloudflare.php', array());

        $this->assertStringContainsString(
            'href="' . admin_url('options-general.php?page=cloudflare') . '"',
            implode('', $links)
        );
    }

    public function testSettingsPageEncodesStoredValuesForItsScript()
    {
        wp_set_current_user($this->createUser('administrator'));
        $email = "o'brien</script><script>alert(1)</script>@example.com";
        update_option(DataStore::EMAIL, $email);

        ob_start();
        try {
            $this->pluginHooks()->cloudflareIndexPage();
        } finally {
            $html = (string) ob_get_clean();
        }

        $this->assertStringContainsString('localStorage.cfEmail = ' . wp_json_encode($email) . ';', $html);
        $this->assertStringNotContainsString($email, $html, 'The raw value would end the script element.');
    }

    /**
     * @return string[]
     */
    private function settingsMenuSlugs()
    {
        $items = isset($GLOBALS['submenu']['options-general.php']) ? $GLOBALS['submenu']['options-general.php'] : array();

        return array_map(function ($item) {
            return $item[2];
        }, $items);
    }
}
