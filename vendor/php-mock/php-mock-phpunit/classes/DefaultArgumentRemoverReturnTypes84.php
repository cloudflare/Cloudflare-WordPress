<?php

namespace phpmock\phpunit;

use phpmock\generator\MockFunctionGenerator;
use PHPUnit\Framework\MockObject\Invocation;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;

/**
 * Removes default arguments from the invocation.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class DefaultArgumentRemoverReturnTypes84 extends InvocationOrder
{
    /**
     * @SuppressWarnings(PHPMD)
     */
    public function invokedDo(Invocation $invocation)
    {
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function matches(Invocation $invocation) : bool
    {
        $iClass = class_exists(Invocation::class);

        if ($iClass
            || $invocation instanceof Invocation\StaticInvocation
        ) {
            $this->removeDefaultArguments(
                $invocation,
                $iClass ? Invocation::class : Invocation\StaticInvocation::class
            );
        } else {
            MockFunctionGenerator::removeDefaultArguments($invocation->parameters);
        }

        return false;
    }

    public function verify() : void
    {
    }

    /**
     * This method is not defined in the interface, but used in
     * PHPUnit_Framework_MockObject_InvocationMocker::hasMatchers().
     *
     * @return boolean
     * @see \PHPUnit_Framework_MockObject_InvocationMocker::hasMatchers()
     */
    public function hasMatchers()
    {
        return false;
    }

    public function toString() : string
    {
        return __CLASS__;
    }

    /**
     * Remove default arguments from StaticInvocation or its children (hack)
     *
     * @SuppressWarnings(PHPMD)
     */
    private function removeDefaultArguments(Invocation $invocation, string $class)
    {
        $remover = function () {
            MockFunctionGenerator::removeDefaultArguments($this->parameters);
        };

        $remover->bindTo($invocation, $class)();
    }
}
