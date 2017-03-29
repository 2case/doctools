<?php
namespace Neos\DocTools\Domain\Service;

/*                                                                        *
 * This script belongs to the Flow package "TYPO3.DocTools".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\MethodReflection;

/**
 * Neos.DocTools parser for Eel helper classes.
 */
class EelHelperClassParser extends AbstractClassParser
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Fusion", path="defaultContext")
     * @var array
     */
    protected $defaultContextSettings;

    /**
     * Get the title from the Eel helper class name
     *
     * @return string
     */
    protected function parseTitle()
    {
        if (($registeredName = array_search($this->className, $this->defaultContextSettings)) !== false) {
            return $registeredName;
        } elseif (preg_match('/\\\\([^\\\\]*)Helper$/', $this->className, $matches)) {
            return $matches[1];
        }

        return $this->className;
    }

    /**
     * Iterate over all methods in the helper class
     *
     * @return array
     */
    protected function parseDescription()
    {
        $description = $this->classReflection->getDescription() . chr(10) . chr(10);

        $description .= 'Implemented in: ``' . $this->className . '``' . chr(10) . chr(10);

        $helperName = $this->parseTitle();
        $helperInstance = new $this->className();

        $methods = $this->getHelperMethods();
        foreach ($methods as $methodReflection) {
            if (!$helperInstance instanceof ProtectedContextAwareInterface || $helperInstance->allowsCallOfMethod($methodReflection->getName())) {
                $methodDescription = $this->getMethodDescription($helperName, $methodReflection);
                $description .= trim($methodDescription) . chr(10) . chr(10);
            }
        }

        return $description;
    }

    /**
     * @param string $helperName
     * @param MethodReflection $methodReflection
     * @return string
     */
    protected function getMethodDescription($helperName, $methodReflection)
    {
        $methodDescription = '';
        $methodName = $methodReflection->getName();

        $methodParameters = [];
        foreach ($methodReflection->getParameters() as $parameterReflection) {
            $methodParameters[$parameterReflection->getName()] = $parameterReflection;
        }

        $parameterNames = array_keys($methodParameters);

        $methodSignature = str_replace('_', '\\_', $helperName . '.' . $methodName . '(' . implode(', ', $parameterNames) . ')');

        $methodDescription .= $methodSignature . chr(10) . str_repeat('^', strlen($methodSignature)) . chr(10) . chr(10);

        if ($methodReflection->getDescription() !== '') {
            $methodDescription .= $methodReflection->getDescription() . chr(10) . chr(10);
        }

        if ($methodReflection->isTaggedWith('param')) {
            $paramTagValues = $methodReflection->getTagValues('param');

            foreach ($paramTagValues as $paramTagValue) {
                $values = explode(' ', $paramTagValue, 3);
                list($parameterType, $parameterName) = $values;
                $parameterName = ltrim($parameterName, '$');
                $parameterDescription = isset($values[2]) ? $values[2] : '';

                $parameterOptionalSuffix = $methodParameters[$parameterName]->isOptional() ? ', *optional*' : '';

                $methodDescription .= trim('* ``' . $parameterName . '`` (' . $parameterType . $parameterOptionalSuffix . ') ' . $parameterDescription) . chr(10);
            }

            $methodDescription .= chr(10);
        }

        if ($methodReflection->isTaggedWith('return')) {
            list($returnTagValue) = $methodReflection->getTagValues('return');

            $values = explode(' ', $returnTagValue, 2);
            list($returnType) = $values;
            $returnDescription = isset($values[1]) ? $values[1] : '';

            $methodDescription .= '**Return** (' . $returnType . ') ' . $returnDescription . chr(10);
        }

        return $methodDescription;
    }

    /**
     * @return array<MethodReflection>
     */
    protected function getHelperMethods()
    {
        $methods = $this->classReflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter($methods, function (MethodReflection $methodReflection) {
            $methodName = $methodReflection->getName();
            if (strpos($methodName, '__') === 0 || $methodName === 'allowsCallOfMethod' || $methodReflection->isTaggedWith('deprecated')) {
                return false;
            }

            return true;
        });
        usort($methods, function (MethodReflection $methodReflection1, MethodReflection $methodReflection2) {
            return strcmp($methodReflection1->getName(), $methodReflection2->getName());
        });

        return $methods;
    }

    /**
     * @return array<\Neos\DocTools\Domain\Model\ArgumentDefinition>
     */
    protected function parseArgumentDefinitions()
    {
        return [];
    }

    /**
     * @return array<\Neos\DocTools\Domain\Model\CodeExample>
     */
    protected function parseCodeExamples()
    {
        return [];
    }

}