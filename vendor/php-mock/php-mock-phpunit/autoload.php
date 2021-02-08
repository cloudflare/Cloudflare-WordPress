<?php

if (class_exists(\PHPUnit\Framework\MockObject\Rule\InvocationOrder::class)) {
    class_alias(
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder::class,
        \PHPUnit\Framework\MockObject\Matcher\Invocation::class
    );
} elseif (! interface_exists(\PHPUnit\Framework\MockObject\Matcher\Invocation::class)) {
    class_alias(
        \PHPUnit_Framework_MockObject_Matcher_Invocation::class,
        \PHPUnit\Framework\MockObject\Matcher\Invocation::class
    );
}

if (! interface_exists(\PHPUnit\Framework\MockObject\Invocation::class)
    && interface_exists(\PHPUnit_Framework_MockObject_Invocation::class)
) {
    class_alias(
        \PHPUnit_Framework_MockObject_Invocation::class,
        \PHPUnit\Framework\MockObject\Invocation::class
    );
}

if (! interface_exists(\PHPUnit\Framework\MockObject\MockObject::class)) {
    class_alias(
        \PHPUnit_Framework_MockObject_MockObject::class,
        \PHPUnit\Framework\MockObject\MockObject::class
    );
}

if (! class_exists(\PHPUnit\Framework\MockObject\Builder\InvocationMocker::class)) {
    class_alias(
        \PHPUnit_Framework_MockObject_Builder_InvocationMocker::class,
        \PHPUnit\Framework\MockObject\Builder\InvocationMocker::class
    );
}

if (class_exists(\PHPUnit\Framework\MockObject\Rule\MethodName::class)) {
    class_alias(
        \PHPUnit\Framework\MockObject\Rule\MethodName::class,
        \PHPUnit\Framework\MockObject\Matcher\MethodName::class
    );
}

if (! class_exists(\PHPUnit\Framework\MockObject\Matcher\MethodName::class)) {
    class_alias(
        \PHPUnit_Framework_MockObject_Matcher_MethodName::class,
        \PHPUnit\Framework\MockObject\Matcher\MethodName::class
    );
}

if (!class_exists(\PHPUnit\Framework\MockObject\InvocationHandler::class)
    && !interface_exists(\PHPUnit\Framework\MockObject\Stub\MatcherCollection::class)
) {
    class_alias(
        \PHPUnit_Framework_MockObject_Stub_MatcherCollection::class,
        \PHPUnit\Framework\MockObject\Stub\MatcherCollection::class
    );
}

if (!class_exists(\PHPUnit\Framework\MockObject\InvocationHandler::class)
    && !class_exists(\PHPUnit\Framework\MockObject\InvocationMocker::class)
) {
    class_alias(
        \PHPUnit_Framework_MockObject_InvocationMocker::class,
        \PHPUnit\Framework\MockObject\InvocationMocker::class
    );
}

if (! class_exists(\PHPUnit\Framework\BaseTestListener::class)) {
    include __DIR__ . '/compatibility/BaseTestListener.php';
    class_alias(
        phpmock\phpunit\MockDisablerPHPUnit7::class,
        phpmock\phpunit\MockDisabler::class
    );
} else {
    class_alias(
        phpmock\phpunit\MockDisablerPHPUnit6::class,
        phpmock\phpunit\MockDisabler::class
    );
}

if (class_exists(\PHPUnit\Runner\Version::class)
    && version_compare(\PHPUnit\Runner\Version::id(), '8.4.0') >= 0
) {
    class_alias(\phpmock\phpunit\DefaultArgumentRemoverReturnTypes84::class, \phpmock\phpunit\DefaultArgumentRemover::class);
    class_alias(\phpmock\phpunit\MockObjectProxyReturnTypes84::class, \phpmock\phpunit\MockObjectProxy::class);
} elseif (class_exists(\PHPUnit\Runner\Version::class)
    && version_compare(\PHPUnit\Runner\Version::id(), '8.1.0') >= 0
) {
    class_alias(\phpmock\phpunit\DefaultArgumentRemoverReturnTypes::class, \phpmock\phpunit\DefaultArgumentRemover::class);
    class_alias(\phpmock\phpunit\MockObjectProxyReturnTypes::class, \phpmock\phpunit\MockObjectProxy::class);
} else {
    class_alias(\phpmock\phpunit\DefaultArgumentRemoverNoReturnTypes::class, \phpmock\phpunit\DefaultArgumentRemover::class);
    class_alias(\phpmock\phpunit\MockObjectProxyNoReturnTypes::class, \phpmock\phpunit\MockObjectProxy::class);
}
