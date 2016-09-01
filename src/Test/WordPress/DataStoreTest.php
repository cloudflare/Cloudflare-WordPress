<?php
namespace CF\WordPress {

	/*
	 * PHPUnit looks for global methods in the current namespace so to mock them we:
	 * 1. Create a class with methods that match the signatures of global methods we want to mock.
	 * 2. Create a global instance of that class
	 * 3. Create global (to the namespace) methods that return our class method
	 * 4. In setup() set our mock to the global instance of the class
	 */
	class WordPressGlobalFunctions {
		public function esc_sql($string) {}
		public function get_option($key) {}
		public function update_option($key, $value) {}
	}

	global $wordPressGlobalFunctions;
	$wordPressGlobalFunctions = new WordPressGlobalFunctions();

	/*
	 * https://codex.wordpress.org/Function_Reference/update_option
	 */
	function update_option($key, $value)
	{
		global $wordPressGlobalFunctions;
		return $wordPressGlobalFunctions->update_option($key, $value);
	}

	/*
	 * https://developer.wordpress.org/reference/functions/get_option/
	 */
	function get_option($key)
	{
		global $wordPressGlobalFunctions;
		return $wordPressGlobalFunctions->get_option($key);
	}

	/*
	 * https://codex.wordpress.org/Function_Reference/esc_sql
	 */
	function esc_sql($string) {
		global $wordPressGlobalFunctions;
		return $wordPressGlobalFunctions->esc_sql($string);
	}
}

namespace CF\Test\WordPress {

    use CF\WordPress\DataStore;
    use CF\API\Plugin;

    class DataStoreTest extends \PHPUnit_Framework_TestCase
	{
		protected $dataStore;
		protected $mockLogger;
		protected $mockWordPressGlobalFunctions;

		public function setup()
		{
			global $wordPressGlobalFunctions;
			$this->mockWordPressGlobalFunctions = $this->getMockBuilder('CF\WordPress\WordPressGlobalFunctions')
				->disableOriginalConstructor()
				->getMock();
			$wordPressGlobalFunctions = $this->mockWordPressGlobalFunctions;
			$this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
				->disableOriginalConstructor()
				->getMock();
			$this->dataStore = new DataStore($this->mockLogger);
		}

		public function testCreateUserDataStoreSavesAPIKeyAndEmail()
		{
			$apiKey = "apiKey";
			$email = "email";

			$this->mockWordPressGlobalFunctions->method('esc_sql')->will(
				$this->returnValueMap(
					array(
						array(DataStore::API_KEY, DataStore::API_KEY),
						array(DataStore::EMAIL, DataStore::EMAIL),
						array($apiKey, $apiKey),
						array($email, $email)
					)
				)
			);

			$this->mockWordPressGlobalFunctions->method('update_option')->will(
				$this->returnValueMap(
					array(
						array(DataStore::API_KEY, $apiKey, true),
						array(DataStore::EMAIL, $email, true)
					)
				)
			);
			$this->assertTrue($this->dataStore->createUserDataStore($apiKey, $email, null, null));
		}

		public function testGetHostAPIUserUniqueIdReturnsNull()
		{
			$this->assertNull($this->dataStore->getHostAPIUserUniqueId());
		}

		public function testGetClientV4APIKeyReturnsCorrectValue()
		{
			$apiKey = "apiKey";
			$this->mockWordPressGlobalFunctions->method('get_option')->willReturn($apiKey);
			$this->assertEquals($apiKey, $this->dataStore->getClientV4APIKey());
		}

		public function testGetHostAPIUserKeyReturnsNull() {
			$this->assertNull($this->dataStore->getHostAPIUserKey());
		}

		public function testGetDomainNameCacheReturnsDomainIfItExistsInCache() {
			$cachedDomain = "cachedDomain";
			$this->mockWordPressGlobalFunctions->method('esc_sql')->with(DataStore::CACHED_DOMAIN_NAME)->willReturn(DataStore::CACHED_DOMAIN_NAME);
			$this->mockWordPressGlobalFunctions->method('get_option')->with(DataStore::CACHED_DOMAIN_NAME)->willReturn($cachedDomain);
			$this->assertEquals($cachedDomain, $this->dataStore->getDomainNameCache());
		}

		public function testSetDomainNameCacheSetsDomain() {
			$domain = "domain";
			$this->mockWordPressGlobalFunctions->method('esc_sql')->will(
				$this->returnValueMap(
					array(
						array(DataStore::CACHED_DOMAIN_NAME, DataStore::CACHED_DOMAIN_NAME),
						array($domain, $domain)
					)
				)
			);
			$this->mockWordPressGlobalFunctions->method('update_option')->with(DataStore::CACHED_DOMAIN_NAME, $domain)->willReturn(true);
			$this->assertTrue($this->dataStore->setDomainNameCache($domain));
		}

		public function testGetCloudFlareEmailReturnsCorrectValue()
		{
			$email = "email";
			$this->mockWordPressGlobalFunctions->method('get_option')->willReturn($email);
			$this->assertEquals($email, $this->dataStore->getCloudFlareEmail());
		}

		public function testGetPluginSettingCallsGetOption() {
			$this->mockWordPressGlobalFunctions->expects($this->once())->method('get_option');
			$this->dataStore->getPluginSetting(Plugin::SETTING_DEFAULT_SETTINGS);
		}

		public function testSetPluginSettingCallsUpdateOption() {
			$this->mockWordPressGlobalFunctions->expects($this->once())->method('update_option');
			$this->dataStore->setPluginSetting(Plugin::SETTING_DEFAULT_SETTINGS,"value");
		}

		public function testGetCallsEscSqlAndGetOption() {
			$this->mockWordPressGlobalFunctions->expects($this->once())->method('get_option');
			$this->mockWordPressGlobalFunctions->expects($this->once())->method('esc_sql');
			$this->dataStore->get("key");
		}

		public function testSetCallsEscSqlAndUpdateOption() {
			$this->mockWordPressGlobalFunctions->expects($this->once())->method('update_option');
			$this->mockWordPressGlobalFunctions->expects($this->exactly(2))->method('esc_sql');
			$this->dataStore->set("key", "value");
		}
	}
}
