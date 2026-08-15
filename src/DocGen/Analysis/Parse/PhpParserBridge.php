<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use function constant;

use PhpAiToolkit\DocGen\DocGenException;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;

/**
 * Creates a PHP parser for both nikic/php-parser 4 and 5.
 *
 * The two major versions expose different factory methods, so the factory
 * is inspected with reflection and the available method is invoked. This
 * keeps the bridge valid for either major version without referencing
 * methods that only one of them ships.
 */
final class PhpParserBridge
{
    private ?Parser $parser = null;

    /**
     * Returns a memoized parser for the newest supported PHP version.
     *
     * @throws DocGenException when the parser factory produces no parser
     */
    public function parser(): Parser
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        $factory = new ParserFactory();
        $reflection = new ReflectionClass($factory);
        if ($reflection->hasMethod('createForNewestSupportedVersion')) {
            $created = $reflection->getMethod('createForNewestSupportedVersion')->invoke($factory);
        } else {
            $created = $reflection->getMethod('create')->invoke($factory, constant(ParserFactory::class . '::PREFER_PHP7'));
        }

        if (!$created instanceof Parser) {
            throw new DocGenException('The installed nikic/php-parser version produced no parser instance.');
        }

        $this->parser = $created;

        return $created;
    }
}
