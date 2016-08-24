<?php

namespace CF\Wordpress {
    function wp_login_url()
    {
        return;
    }

    function get_admin_url()
    {
        return;
    }
}

namespace CF\WordPress\Test {

    use \CF\Integration\DefaultIntegration;
    use \CF\WordPress\Constants\Plans;
	use \CF\WordPress\PluginActions;

	class PluginActionsTest extends \PHPUnit_Framework_TestCase
    {
        private $mockConfig;
        private $mockDataStore;
        private $mockDefaultIntegration;
		private $mockLogger;
		private $mockPluginAPIClient;
		private $mockWordPressAPI;
        private $mockWordPressClientAPI;
		private $mockRequest;
        private $pluginActions;

        public function setup()
        {
            $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
                ->disableOriginalConstructor()
                ->getMock();
			$this->mockLogger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
				->disableOriginalConstructor()
				->getMock();
			$this->mockPluginAPIClient = $this->getMockBuilder('CF\API\Plugin')
				->disableOriginalConstructor()
				->getMock();
			$this->mockWordPressAPI = $this->getMockBuilder('CF\WordPress\WordPressAPI')
				->disableOriginalConstructor()
				->getMock();
			$this->mockWordPressClientAPI = $this->getMockBuilder('CF\WordPress\WordPressClientAPI')
				->disableOriginalConstructor()
				->getMock();
			$this->mockRequest = $this->getMockBuilder('CF\API\Request')
				->disableOriginalConstructor()
				->getMock();

			$this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
            $this->pluginActions = new PluginActions($this->mockDefaultIntegration, $this->mockPluginAPIClient, $this->mockRequest);
			$this->pluginActions->setClientAPI($this->mockWordPressClientAPI);
        }

        public function testReturnApplyDefaultSettingsWithZoneWithPlanBIZ()
        {
			$this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
                array(
                    'result' => array(
                        'plan' => array(
                            'legacy_id' => Plans::BIZ_PLAN,
                        ),
                    ),
                )
            );
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
            $this->mockWordPressClientAPI->method('createPageRule')->willReturn(true);

            $this->mockWordPressClientAPI->expects($this->exactly(15))->method('changeZoneSettings');
            $this->mockWordPressClientAPI->expects($this->exactly(2))->method('createPageRule');

            $this->pluginActions->applyDefaultSettings();
        }

        public function testReturnApplyDefaultSettingsWithZoneWithFreePlan()
        {
			$this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
                array(
                    'result' => array(
                        'plan' => array(
                            'legacy_id' => Plans::FREE_PLAN,
                        ),
                    ),
                )
            );
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
            $this->mockWordPressClientAPI->method('createPageRule')->willReturn(true);

            $this->mockWordPressClientAPI->expects($this->exactly(13))->method('changeZoneSettings');
            $this->mockWordPressClientAPI->expects($this->exactly(2))->method('createPageRule');

            $this->pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\ZoneSettingFailException
         */
        public function testReturnApplyDefaultSettingsZoneDetailsThrowsZoneSettingFailException()
        {
			$this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(false);

            $this->pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\ZoneSettingFailException
         */
        public function testReturnApplyDefaultSettingsChangeZoneSettingsThrowsZoneSettingFailException()
        {
			$this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(false);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(true);
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(false);

            $this->pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\PageRuleLimitException
         */
        public function testReturnApplyDefaultSettingsCreatePageRuleThrowsPageRuleLimitException()
        {
			$this->mockRequest->method('getUrl')->willReturn('/plugin/:id/settings/default_settings');

			$this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(
				array(
					'result' => array(
						'plan' => array(
							'legacy_id' => Plans::ENT_PLAN,
						),
					),
				)
			);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(true);
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
            $this->mockWordPressClientAPI->method('createPageRule')->willReturn(false);

            $this->pluginActions->applyDefaultSettings();
        }
    }
}
