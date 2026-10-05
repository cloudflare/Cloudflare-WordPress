<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\API\Plugin;
use Cloudflare\APO\Integration\DefaultLogger;
use Cloudflare\APO\WordPress\DataStore;

/**
 * Credentials and plugin settings are stored as WordPress options.
 */
class DataStoreTest extends IntegrationTestCase
{
    /**
     * @var DataStore
     */
    private $dataStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataStore = new DataStore(new DefaultLogger());
    }

    public function testCredentialsAreStoredAsOptions()
    {
        $this->assertTrue($this->dataStore->createUserDataStore('stored-api-key', 'stored@example.com', null, null));

        $this->assertSame('stored-api-key', get_option(DataStore::API_KEY));
        $this->assertSame('stored@example.com', get_option(DataStore::EMAIL));
        $this->assertSame('stored-api-key', $this->dataStore->getClientV4APIKey());
        $this->assertSame('stored@example.com', $this->dataStore->getCloudFlareEmail());
    }

    public function testDomainNameIsCachedAsAnOption()
    {
        $this->dataStore->setDomainNameCache('example.com');

        $this->assertSame('example.com', get_option(DataStore::CACHED_DOMAIN_NAME));
        $this->assertSame('example.com', $this->dataStore->getDomainNameCache());
    }

    public function testEmptyDomainNameCacheReadsAsNull()
    {
        delete_option(DataStore::CACHED_DOMAIN_NAME);

        $this->assertNull($this->dataStore->getDomainNameCache());
    }

    public function testOnlyKnownPluginSettingsCanBeRead()
    {
        $this->setPluginSetting(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION, 'on');
        update_option('siteurl_backup', 'not a plugin setting');

        try {
            $this->assertSame('on', $this->dataStore->getPluginSetting(Plugin::SETTING_AUTOMATIC_PLATFORM_OPTIMIZATION)[Plugin::SETTING_VALUE_KEY]);
            $this->assertFalse($this->dataStore->getPluginSetting('siteurl_backup'));
        } finally {
            delete_option('siteurl_backup');
        }
    }

    public function testClearDataStoreRemovesCredentialsAndEverySetting()
    {
        foreach (Plugin::getPluginSettingsKeys() as $settingId) {
            $this->setPluginSetting($settingId, 'on');
        }

        $this->dataStore->clearDataStore();

        foreach (array_merge(Plugin::getPluginSettingsKeys(), array(DataStore::API_KEY, DataStore::EMAIL, DataStore::CACHED_DOMAIN_NAME)) as $option) {
            $this->assertFalse(get_option($option), $option . ' should be deleted.');
        }
    }
}
