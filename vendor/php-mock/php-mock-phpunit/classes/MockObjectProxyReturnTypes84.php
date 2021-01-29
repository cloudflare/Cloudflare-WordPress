<?php

namespace phpmock\phpunit;

use PHPUnit\Framework\MockObject\Builder\InvocationMocker as BuilderInvocationMocker;
use PHPUnit\Framework\MockObject\InvocationHandler;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use PHPUnit\Framework\MockObject\MockObject;
use phpmock\integration\MockDelegateFunctionBuilder;

/**
 * Proxy for PHPUnit's PHPUnit_Framework_MockObject_MockObject.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class MockObjectProxyReturnTypes84 implements MockObject
{
    /**
     * @var MockObject $mockObject The mock object.
     */
    private $mockObject;

    /**
     * Inject the subject.
     *
     * @param MockObject $mockObject   The subject.
     */
    public function __construct(MockObject $mockObject)
    {
        $this->mockObject = $mockObject;
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function __phpunit_getInvocationHandler(): InvocationHandler
    {
        return $this->mockObject->__phpunit_getInvocationHandler();
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function __phpunit_setOriginalObject($originalObject) : void
    {
        // @codingStandardsIgnoreEnd
        $this->mockObject->__phpunit_setOriginalObject($originalObject);
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function __phpunit_verify(bool $unsetInvocationMocker = true) : void
    {
        // @codingStandardsIgnoreEnd
        $this->mockObject->__phpunit_verify($unsetInvocationMocker);
    }

    public function expects(InvocationOrder $matcher) : BuilderInvocationMocker
    {
        return $this->mockObject->expects($matcher)->method(MockDelegateFunctionBuilder::METHOD);
    }

    /**
     * This method is not part of the contract but was found in
     * PHPUnit's mocked_class.tpl.dist.
     *
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function __phpunit_hasMatchers() : bool
    {
        // @codingStandardsIgnoreEnd
        return $this->mockObject->__phpunit_hasMatchers();
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    // @codingStandardsIgnoreStart
    public function __phpunit_setReturnValueGeneration(bool $returnValueGeneration) : void
    {
        // @codingStandardsIgnoreEnd
        $this->mockObject->__phpunit_setReturnValueGeneration($returnValueGeneration);
    }
}
