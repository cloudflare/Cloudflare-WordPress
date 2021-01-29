<?php

namespace phpmock\phpunit;

use phpmock\integration\MockDelegateFunctionBuilder;
use PHPUnit\Framework\MockObject\Builder\InvocationMocker;
use PHPUnit\Framework\MockObject\ConfigurableMethod;
use PHPUnit\Framework\MockObject\InvocationHandler;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\Matcher\MethodName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\MatcherCollection;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Type\Type;

/**
 * Tests MockObjectProxyTest.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see MockObjectProxy
 * @requires PHPUnit 4.5.0
 */
class MockObjectProxyTest extends TestCase
{

    /**
     * Tests expects()
     *
     * @test
     */
    public function testExpects()
    {
        $matcher = $this->getMockBuilder(Invocation::class)->getMock();

        $methods = class_exists(ConfigurableMethod::class)
            ? new ConfigurableMethod(
                MockDelegateFunctionBuilder::METHOD,
                $this->prophesize(Type::class)->reveal()
            )
            : [MockDelegateFunctionBuilder::METHOD];

        if (class_exists(\PHPUnit\Runner\Version::class)
            && version_compare(\PHPUnit\Runner\Version::id(), '8.4.0') >= 0
        ) {
            $invocationHandler = new InvocationHandler([$methods], false);
            $invocationMocker = $invocationHandler->expects($matcher);
        } else {
            $invocationMocker = new InvocationMocker(
                $this->prophesize(MatcherCollection::class)->reveal(),
                $this->prophesize(Invocation::class)->reveal(),
                $methods
            );
        }

        $prophecy = $this->prophesize(MockObject::class);
        $prophecy->expects($matcher)->willReturn($invocationMocker);
        $mock = $prophecy->reveal();

        $proxy = new MockObjectProxy($mock);

        $result = $proxy->expects($matcher);
        $this->assertEquals($invocationMocker, $result);

        $this->assertSame(
            (new MethodName(MockDelegateFunctionBuilder::METHOD))->toString(),
            $this->getMethodMatcher($invocationMocker)->toString()
        );
    }

    private function getMethodMatcher($invocationMocker)
    {
        if (class_exists(\PHPUnit\Runner\Version::class)
            && version_compare(\PHPUnit\Runner\Version::id(), '8.4.0') >= 0
        ) {
            $reflection = new \ReflectionClass(InvocationMocker::class);
            $property = $reflection->getProperty('matcher');
            $property->setAccessible(true);
            return $property->getValue($invocationMocker)->getMethodNameRule();
        }

        return $invocationMocker->getMatcher()->methodNameMatcher ??
            $invocationMocker->getMatcher()->getMethodNameMatcher();
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
        $prophecy = $this->prophesize(MockObject::class);
        $prophecy->__phpunit_hasMatchers()->willReturn(true);
        $mock = $prophecy->reveal();

        $proxy = new MockObjectProxy($mock);

        $result = $proxy->__phpunit_hasMatchers();
        $this->assertTrue($result);
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
    public function testProxiedMethods($method, array $arguments = [], $expected = null)
    {
        $prophecy = $this->prophesize(MockObject::class);
        if ($expected) {
            call_user_func_array([$prophecy, $method], $arguments)->willReturn($expected)->shouldBeCalledTimes(1);
        } else {
            call_user_func_array([$prophecy, $method], $arguments)->shouldBeCalledTimes(1);
        }
        $mock = $prophecy->reveal();

        $proxy = new MockObjectProxy($mock);

        $result = call_user_func_array([$proxy, $method], $arguments);

        if ($expected) {
            $this->assertSame($expected, $result);
        }
    }

    /**
     * Returns the test cases for testProxiedMethods().
     *
     * @return array Test cases.
     *
     * @SuppressWarnings(PHPMD)
     */
    public function provideTestProxiedMethods()
    {
        $return = [];
        if (class_exists(\PHPUnit\Runner\Version::class)
            && version_compare(\PHPUnit\Runner\Version::id(), '8.4.0') >= 0
        ) {
            $return[] = ['__phpunit_getInvocationHandler', [], new InvocationHandler([], false)];
        } else {
            $return[] = [
                '__phpunit_getInvocationMocker',
                [],
                new \PHPUnit\Framework\MockObject\InvocationMocker([], true)
            ];
        }

        $return[] = ['__phpunit_setOriginalObject', ['bar']];
        $return[] = ['__phpunit_verify', [true]];
        return $return;
    }
}
