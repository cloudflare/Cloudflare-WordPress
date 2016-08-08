<?php

namespace CF\Test\WordPress;

use CF\WordPress\Constants\Plans;

class PlansTest extends \PHPUnit_Framework_TestCase
{
    public function testPlanNeedsUpgradeAllCases()
    {
        // FREE_PLAN
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::FREE_PLAN, Plans::FREE_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::FREE_PLAN, Plans::PRO_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::FREE_PLAN, Plans::BIZ_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::FREE_PLAN, Plans::ENT_PLAN));

        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::PRO_PLAN, Plans::FREE_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::PRO_PLAN, Plans::PRO_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::PRO_PLAN, Plans::BIZ_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::PRO_PLAN, Plans::ENT_PLAN));

        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::BIZ_PLAN, Plans::FREE_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::BIZ_PLAN, Plans::PRO_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::BIZ_PLAN, Plans::BIZ_PLAN));
        $this->assertTrue(Plans::PlanNeedsUpgrade(Plans::BIZ_PLAN, Plans::ENT_PLAN));

        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::ENT_PLAN, Plans::FREE_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::ENT_PLAN, Plans::PRO_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::ENT_PLAN, Plans::BIZ_PLAN));
        $this->assertFalse(Plans::PlanNeedsUpgrade(Plans::ENT_PLAN, Plans::ENT_PLAN));
    }
}
