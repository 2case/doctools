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

use Neos\DocTools\Domain\Model\ArgumentDefinition;
use Neos\DocTools\Domain\Model\CodeExample;

/**
 * Neos.DocTools parser for Fluid ViewHelper classes.
 */
class FluidViewHelperClassParser extends AbstractClassParser
{
    const PATTERN_CODE_EXAMPLES = '/<code title="(?P<title>[^"]+)">\n(?P<code>.*?)<\/code>\n\s*<output>\n(?P<output>.*?)<\/output>/s';
    const PATTERN_DESCRIPTION = '/(?P<description>.*)(?=\n\s=\sExamples\s=\n)/s';

    /**
     * @return string
     */
    protected function parseTitle()
    {
        $classNameWithoutSuffix = substr($this->className, 0, -10);
        foreach ($this->options['namespaces'] as $namespaceIdentifier => $fullyQualifiedNamespace) {
            if (strpos($this->className, $fullyQualifiedNamespace) === 0) {
                $titleSegments = explode('\\', substr($classNameWithoutSuffix, strlen($fullyQualifiedNamespace) + 1));

                return sprintf('%s:%s', $namespaceIdentifier, implode('.', array_map('lcfirst', $titleSegments)));
            }
        }

        return substr($this->className, strrpos($this->className, '\\') + 1);
    }

    /**
     * @return array
     */
    protected function parseDescription()
    {
        $description = $this->classReflection->getDescription();
        $matches = [];
        preg_match(self::PATTERN_DESCRIPTION, $description, $matches);
        $description = isset($matches['description']) ? $matches['description'] : $description;

        $description .= chr(10) . chr(10) . ':Implementation: ' . str_replace('\\', '\\\\', $this->className) . chr(10);

        return $description;
    }

    /**
     * @return array<\Neos\DocTools\Domain\Model\ArgumentDefinition>
     */
    protected function parseArgumentDefinitions()
    {
        $viewHelper = new $this->className;
        $viewHelperArguments = $viewHelper->prepareArguments();
        $argumentDefinitions = [];
        foreach ($viewHelperArguments as $viewHelperArgument) {
            $argumentDefinitions[] = new ArgumentDefinition($viewHelperArgument->getName(), $viewHelperArgument->getType(), $viewHelperArgument->getDescription(), $viewHelperArgument->isRequired(), $viewHelperArgument->getDefaultValue());
        }

        return $argumentDefinitions;
    }

    /**
     * @return array<\Neos\DocTools\Domain\Model\CodeExample>
     */
    protected function parseCodeExamples()
    {
        $matches = [];
        preg_match_all(self::PATTERN_CODE_EXAMPLES, $this->classReflection->getDescription(), $matches, PREG_SET_ORDER);
        $examples = [];
        foreach ($matches as $match) {
            $examples[] = new CodeExample(trim($match['title']), trim($match['code']), 'xml', trim($match['output']));
        }

        return $examples;
    }
}
