<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Integration\DefaultLogger;
use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;
use Cloudflare\APO\Tests\Integration\Support\WpDieException;
use Cloudflare\APO\WordPress\DataStore;
use Cloudflare\APO\WordPress\Hooks;
use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that run against the real WordPress install in wp-env.
 *
 * Every test starts with Cloudflare credentials for the site's domain, an
 * HttpRecorder in front of the Cloudflare API, and wp_die() turned into a
 * WpDieException. Posts and users created through the helpers, the plugin's
 * options and the object cache are cleaned up afterwards.
 */
abstract class IntegrationTestCase extends TestCase
{
    /**
     * Zone id in the 32 character format the plugin's routes expect. This is
     * the example zone id from Cloudflare's API documentation.
     */
    protected const ZONE_ID = '023e105f4ecef8ad9ca31a8372d0c353';

    protected const EMAIL = 'integration@example.com';

    /**
     * Not a real credential: the example Global API Key from Cloudflare's API
     * documentation, shortened to 37 lowercase hex characters so it matches
     * the pre-2026 Global API Key format that Client::isGlobalApiKey() detects.
     */
    protected const GLOBAL_API_KEY = 'c2547eb745079dac9320b638f5e225cf483cc';

    /**
     * @var HttpRecorder
     */
    protected $http;

    /**
     * @var int[]
     */
    private $postIds = array();

    /**
     * @var int[]
     */
    private $userIds = array();

    protected function setUp(): void
    {
        parent::setUp();

        wp_cache_flush();

        $this->http = new HttpRecorder();
        $this->http->start();

        foreach (array('wp_die_handler', 'wp_die_ajax_handler', 'wp_die_json_handler') as $filter) {
            add_filter($filter, array($this, 'wpDieHandler'));
        }

        update_option(DataStore::API_KEY, self::GLOBAL_API_KEY);
        update_option(DataStore::EMAIL, self::EMAIL);
        update_option(DataStore::CACHED_DOMAIN_NAME, $this->siteDomain());
    }

    protected function tearDown(): void
    {
        // Every step runs even when an earlier one throws, for example because
        // another plugin fails while a post is deleted, so no fixture or
        // setting leaks into the next test. The first failure is rethrown.
        $failures = array();
        $cleanup = function (callable $step) use (&$failures) {
            try {
                $step();
            } catch (\Throwable $e) {
                $failures[] = $e;
            }
        };

        // Deleting posts can trigger purges, so the recorder stays active
        // until the fixtures are gone.
        foreach ($this->postIds as $postId) {
            $cleanup(function () use ($postId) {
                wp_delete_post($postId, true);
            });
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($this->userIds as $userId) {
            $cleanup(function () use ($userId) {
                wp_delete_user($userId);
            });
        }
        $cleanup(function () {
            wp_set_current_user(0);
            (new DataStore(new DefaultLogger()))->clearDataStore();
        });

        foreach (array('wp_die_handler', 'wp_die_ajax_handler', 'wp_die_json_handler') as $filter) {
            remove_filter($filter, array($this, 'wpDieHandler'));
        }
        $this->http->stop();

        wp_cache_flush();

        parent::tearDown();

        if ($failures) {
            throw $failures[0];
        }
    }

    /**
     * @return callable
     */
    public function wpDieHandler()
    {
        return array($this, 'throwWpDie');
    }

    /**
     * @param mixed $message
     */
    public function throwWpDie($message)
    {
        throw new WpDieException(is_string($message) ? $message : '');
    }

    /**
     * The Hooks instance cloudflare.loader.php registered its callbacks with.
     *
     * @return Hooks
     */
    protected function pluginHooks()
    {
        foreach ($GLOBALS['wp_filter']['init']->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && $callback['function'][0] instanceof Hooks) {
                    return $callback['function'][0];
                }
            }
        }

        $this->fail('The plugin did not register its Hooks instance on init.');
    }

    /**
     * Priority a Hooks method is registered with on an action or filter.
     *
     * @param string $hookName
     * @param string $method
     *
     * @return int|false
     */
    protected function hookPriority($hookName, $method)
    {
        return has_filter($hookName, array($this->pluginHooks(), $method));
    }

    /**
     * Host name of the WordPress site, used as the Cloudflare zone name.
     *
     * @return string
     */
    protected function siteDomain()
    {
        return (string) wp_parse_url(home_url(), PHP_URL_HOST);
    }

    /**
     * Store a plugin setting the way the settings page does.
     *
     * @param string $settingId One of Plugin::getPluginSettingsKeys().
     * @param mixed  $value
     */
    protected function setPluginSetting($settingId, $value)
    {
        update_option($settingId, array(
            Plugin::SETTING_ID_KEY => $settingId,
            Plugin::SETTING_VALUE_KEY => $value,
            Plugin::SETTING_EDITABLE_KEY => true,
            Plugin::SETTING_MODIFIED_DATE_KEY => date('c'),
        ));
    }

    /**
     * Answer the zone lookup for the site's domain with ZONE_ID.
     */
    protected function respondWithZone()
    {
        $this->http->respondTo('GET', 'zones', HttpRecorder::success(array(
            array('id' => self::ZONE_ID, 'name' => $this->siteDomain()),
        )));
    }

    /**
     * @param array $postData Arguments for wp_insert_post().
     *
     * @return int
     */
    protected function createPost(array $postData)
    {
        $postId = wp_insert_post($postData + array('post_title' => 'Integration test post'), true);
        $this->assertIsInt($postId);
        $this->postIds[] = $postId;

        return $postId;
    }

    /**
     * @param string $role
     *
     * @return int
     */
    protected function createUser($role)
    {
        $userId = wp_insert_user(array(
            'user_login' => 'cf_' . $role . '_' . wp_generate_password(6, false),
            'user_pass' => wp_generate_password(),
            'role' => $role,
        ));
        $this->assertIsInt($userId);
        $this->userIds[] = $userId;

        return $userId;
    }
}
