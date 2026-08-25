<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use function constant;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;

/**
 * Creates a parser under both supported nikic/php-parser majors.
 */
final class ParserFactoryBridge
{
    private ?Parser $parser = null;

    /**
     * Returns a memoized parser, or null when the installed factory is unsupported.
     */
    public function parser(): ?Parser
    {
        if ($this->parser !== null) {
            return $this->parser;
        }

        $factory = new ParserFactory();
        $reflection = new ReflectionClass($factory);

        try {
            if ($reflection->hasMethod('createForNewestSupportedVersion')) {
                $created = $reflection->getMethod('createForNewestSupportedVersion')->invoke($factory);
            } else {
                $created = $reflection->getMethod('create')->invoke($factory, constant(ParserFactory::class . '::PREFER_PHP7'));
            }
        } catch (ReflectionException) {
            return null;
        }

        if (!$created instanceof Parser) {
            return null;
        }

        $this->parser = $created;

        return $created;
    }
}
