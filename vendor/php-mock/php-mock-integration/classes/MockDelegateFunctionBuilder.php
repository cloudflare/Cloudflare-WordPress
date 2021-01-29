<?php

namespace phpmock\integration;

use phpmock\generator\ParameterBuilder;
use SebastianBergmann\Template\Template;

/**
 * Defines a MockDelegateFunction.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @internal
 */
class MockDelegateFunctionBuilder
{
    
    /**
     * The delegation method name.
     */
    const METHOD = "delegate";
    
    /**
     * @var string The namespace of the build class.
     */
    private $namespace;
    
    /**
     * @var Template The MockDelegateFunction template.
     */
    private $template;
    
    /**
     * Instantiation.
     */
    public function __construct()
    {
        $this->template = new Template(__DIR__ . '/MockDelegateFunction.tpl');
    }
    
    /**
     * Builds a MockDelegateFunction for a function.
     *
     * @param string|null $functionName The mocked function.
     *
     * @SuppressWarnings(PHPMD)
     */
    public function build($functionName = null)
    {
        $parameterBuilder = new ParameterBuilder();
        $parameterBuilder->build($functionName);
        $signatureParameters = $parameterBuilder->getSignatureParameters();

        /**
         * If a class with the same signature exists, it is considered equivalent
         * to the generated class.
         */
        $hash = md5($signatureParameters);
        $this->namespace = __NAMESPACE__ . $hash;
        if (class_exists($this->getFullyQualifiedClassName())) {
            return;
        }
        
        $data = [
            "namespace"           => $this->namespace,
            "signatureParameters" => $signatureParameters,
        ];
        $this->template->setVar($data, false);
        $definition = $this->template->render();
        
        eval($definition);
    }

    /**
     * Returns the fully qualified class name
     *
     * @return string The class name.
     */
    public function getFullyQualifiedClassName()
    {
        return "$this->namespace\\MockDelegateFunction";
    }
}
