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

    use CF\API\Request;
    use CF\Integration\DefaultIntegration;
    use CF\WordPress\Constants\Plans;

    class PluginActionsTest extends \PHPUnit_Framework_TestCase
    {
        private $mockClientAPI;
        private $mockConfig;
        private $mockWordPressAPI;
        private $mockDataStore;
        private $mockLogger;
        private $mockDefaultIntegration;
        private $mockWordPressClientAPI;

        public function setup()
        {
            $this->mockClientAPI = $this->getMockBuilder('CF\API\Client')
            ->disableOriginalConstructor()
            ->getMock();
            $this->mockWordPressClientAPI = $this->getMockBuilder('CF\WordPress\WordPressClientAPI')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockConfig = $this->getMockBuilder('CF\Integration\DefaultConfig')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockWordPressAPI = $this->getMockBuilder('CF\WordPress\WordPressAPI')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockDataStore = $this->getMockBuilder('CF\WordPress\DataStore')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockLogger = $this->getMockBuilder('CF\Integration\DefaultLogger')
                ->disableOriginalConstructor()
                ->getMock();
            $this->mockDefaultIntegration = new DefaultIntegration($this->mockConfig, $this->mockWordPressAPI, $this->mockDataStore, $this->mockLogger);
        }

        public function testReturnApplyDefaultSettingsWithZoneWithPlanBIZ()
        {
            $request = new Request(null, null, null, null);

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

            $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
                ->setMethods(array('createWordPressClientAPI'))
                 ->setConstructorArgs(array($this->mockDefaultIntegration, $this->mockClientAPI, $request))
                ->getMock();

            $pluginActions->method('createWordPressClientAPI')
                ->willReturn($this->mockWordPressClientAPI);

            $pluginActions->applyDefaultSettings();
        }

        public function testReturnApplyDefaultSettingsWithZoneWithFreePlan()
        {
            $request = new Request(null, null, null, null);

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

            $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
                ->setMethods(array('createWordPressClientAPI'))
                 ->setConstructorArgs(array($this->mockDefaultIntegration, $this->mockClientAPI, $request))
                ->getMock();

            $pluginActions->method('createWordPressClientAPI')
                ->willReturn($this->mockWordPressClientAPI);

            $pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\ZoneSettingFailException
         */
        public function testReturnApplyDefaultSettingsZoneDetailsThrowsZoneSettingFailException()
        {
            $request = new Request(null, null, null, null);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(false);

            $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
                ->setMethods(array('createWordPressClientAPI'))
                 ->setConstructorArgs(array($this->mockDefaultIntegration, $this->mockClientAPI, $request))
                ->getMock();

            $pluginActions->method('createWordPressClientAPI')
                ->willReturn($this->mockWordPressClientAPI);

            $pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\ZoneSettingFailException
         */
        public function testReturnApplyDefaultSettingsChangeZoneSettingsThrowsZoneSettingFailException()
        {
            $request = new Request(null, null, null, null);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(false);

            $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
                ->setMethods(array('createWordPressClientAPI'))
                 ->setConstructorArgs(array($this->mockDefaultIntegration, $this->mockClientAPI, $request))
                ->getMock();

            $pluginActions->method('createWordPressClientAPI')
                ->willReturn($this->mockWordPressClientAPI);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(true);
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(false);

            $pluginActions->applyDefaultSettings();
        }

        /**
         * @expectedException CF\API\Exception\PageRuleLimitException
         */
        public function testReturnApplyDefaultSettingsCreatePageRuleThrowsPageRuleLimitException()
        {
            $request = new Request(null, null, null, null);

            $this->mockWordPressClientAPI->method('zoneGetDetails')->willReturn(true);
            $this->mockWordPressClientAPI->method('responseOk')->willReturn(true);
            $this->mockWordPressClientAPI->method('changeZoneSettings')->willReturn(true);
            $this->mockWordPressClientAPI->method('createPageRule')->willReturn(false);

            $pluginActions = $this->getMockBuilder('CF\WordPress\PluginActions')
                ->setMethods(array('createWordPressClientAPI'))
                 ->setConstructorArgs(array($this->mockDefaultIntegration, $this->mockClientAPI, $request))
                ->getMock();

            $pluginActions->method('createWordPressClientAPI')
                ->willReturn($this->mockWordPressClientAPI);

            $pluginActions->applyDefaultSettings();
        }
    }
}
