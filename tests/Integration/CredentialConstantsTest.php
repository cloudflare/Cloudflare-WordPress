<?php

namespace Cloudflare\APO\Tests\Integration;

use Cloudflare\APO\Integration\DefaultConfig;
use Cloudflare\APO\Integration\DefaultIntegration;
use Cloudflare\APO\Integration\DefaultLogger;
use Cloudflare\APO\Tests\Integration\Support\HttpRecorder;
use Cloudflare\APO\WordPress\DataStore;
use Cloudflare\APO\WordPress\WordPressAPI;
use Cloudflare\APO\WordPress\WordPressClientAPI;

/**
 * Credentials defined in wp-config.php take precedence over stored options.
 *
 * Constants cannot be undefined, so every test runs in its own PHP process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CredentialConstantsTest extends IntegrationTestCase
{
    public function testConstantCredentialsAreSentToTheApiInsteadOfStoredOnes()
    {
        $apiToken = str_repeat('Zx9', 13) . 'q';
        define('CLOUDFLARE_EMAIL', 'constant@example.com');
        define('CLOUDFLARE_API_KEY', $apiToken);
        $dataStore = new DataStore(new DefaultLogger());
        $path = 'zones/' . self::ZONE_ID . '/settings/always_use_https';
        $this->http->respondTo('GET', $path, HttpRecorder::success(array('id' => 'always_use_https', 'value' => 'off')));

        $clientApi = new WordPressClientAPI(new DefaultIntegration(
            new DefaultConfig(),
            new WordPressAPI($dataStore),
            $dataStore,
            new DefaultLogger()
        ));
        $clientApi->getZoneSetting(self::ZONE_ID, 'always_use_https');

        $this->assertSame('constant@example.com', $dataStore->getCloudFlareEmail());
        $this->assertSame($apiToken, $dataStore->getClientV4APIKey());
        $this->assertSame('Bearer ' . $apiToken, $this->http->requestsTo('GET', $path)[0]['headers']['Authorization']);
    }

    public function testSavingCredentialsKeepsStoredOptionsWhenConstantsAreDefined()
    {
        define('CLOUDFLARE_EMAIL', 'constant@example.com');
        define('CLOUDFLARE_API_KEY', self::GLOBAL_API_KEY);
        $dataStore = new DataStore(new DefaultLogger());

        $this->assertTrue($dataStore->createUserDataStore('other-key', 'other@example.com', null, null));

        $this->assertSame(self::GLOBAL_API_KEY, get_option(DataStore::API_KEY));
        $this->assertSame(self::EMAIL, get_option(DataStore::EMAIL));
    }

    public function testDomainNameConstantOverridesTheCachedDomain()
    {
        define('CLOUDFLARE_DOMAIN_NAME', 'example.com');
        $dataStore = new DataStore(new DefaultLogger());

        $dataStore->setDomainNameCache('other.example');

        $this->assertSame('example.com', $dataStore->getDomainNameCache());
        $this->assertSame($this->siteDomain(), get_option(DataStore::CACHED_DOMAIN_NAME), 'The cache must not be overwritten.');
        $this->assertSame(array('example.com'), (new WordPressAPI($dataStore))->getDomainList());
    }
}
