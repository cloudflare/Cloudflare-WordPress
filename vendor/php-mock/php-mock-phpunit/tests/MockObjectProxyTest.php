<?php

namespace phpmock\phpunit;

use \PHPUnit_Framework_MockObject_Builder_InvocationMocker as InvocationMocker;
use phpmock\integration\MockDelegateFunctionBuilder;

/**
 * Tests MockObjectProxyTest.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see MockObjectProxy
 * @requires PHPUnit 4.5.0
 */
class MockObjectProxyTest extends \PHPUnit_Framework_TestCase
{
 
    /**
     * Tests expects()
     *
     * @test
     */
    public function testExpects()
    {
        $matcher = $this->getMock(\PHPUnit_Framework_MockObject_Matcher_Invocation::class);
        
        $invocationMocker = $this->getMock(InvocationMocker::class, [], [], '', false);
        $invocationMocker->expects($this->once())->method("method")
                ->with(MockDelegateFunctionBuilder::METHOD)->willReturn($invocationMocker);
        
        $prophecy = $this->prophesize(\PHPUnit_Framework_MockObject_MockObject::class);
        $prophecy->expects($matcher)->willReturn($invocationMocker);
        $mock = $prophecy->reveal();
        
        $proxy = new MockObjectProxy($mock);
        
        $result = $proxy->expects($matcher);
        $this->assertEquals($invocationMocker, $result);
    }
    
    /**
     * Tests delegation of __phpunit_hasMatchers().
     *
     * Before PHPUnit-5 __phpunit_hasMatchers() was not part of the contract.
     * But it was used within PHPUnit as it would be. Unfortunately the
     * mocking framework Prophecy will not allow to mock this method.
     *
     * @test
     * @requires PHPUnit 5
     */
    public function testHasMatcher()
    {
        $prophecy = $this->prophesize(\PHPUnit_Framework_MockObject_MockObject::class);
        $prophecy->__phpunit_hasMatchers()->willReturn("foo");
        $mock = $prophecy->reveal();
        
        $proxy = new MockObjectProxy($mock);
        
        $result = $proxy->__phpunit_hasMatchers();
        $this->assertEquals("foo", $result);
    }
    
    /**
     * Tests calling the proxy forwards the call to the subject.
     *
     * @param string $method    The proxy method.
     * @param array  $arguments The optional arguments.
     *
     * @test
     * @dataProvider provideTestProxiedMethods
     */
    public function testProxiedMethods($method, array $arguments = [], $expected = "foo")
    {
        $prophecy = $this->prophesize(\PHPUnit_Framework_MockObject_MockObject::class);
        call_user_func_array([$prophecy, $method], $arguments)->willReturn($expected);
        $mock = $prophecy->reveal();
        
        $proxy = new MockObjectProxy($mock);
        
        $result = call_user_func_array([$proxy, $method], $arguments);
        $this->assertEquals($expected, $result);
    }

    /**
     * Returns the test cases for testProxiedMethods().
     *
     * @return array Test cases.
     */
    public function provideTestProxiedMethods()
    {
        return [
            ["__phpunit_getInvocationMocker"],
            ["__phpunit_setOriginalObject", ["bar"]],
            ["__phpunit_verify"],
        ];
    }
}
