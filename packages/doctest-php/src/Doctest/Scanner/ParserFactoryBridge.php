<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Scanner;

use function constant;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * Creates a PHP parser for both nikic/php-parser 4 and 5.
 *
 * An adaptation the port needs: k-kinzal/doctest-php calls
 * createForNewestSupportedVersion(), which only php-parser 5 ships, while this
 * package also supports php-parser 4. The factory is inspected with reflection
 * and the available method is invoked, so the bridge stays valid for either
 * major without naming a method only one of them has.
 *
 * @visibility parent
 */
final class ParserFactoryBridge
{
    /**
     * Returns a parser for the newest PHP version the installed parser supports.
     *
     * @throws RuntimeException when the factory exposes no supported creation method or produces no parser
     */
    public function create(): Parser
    {
        $factory = new ParserFactory();
        $reflection = new ReflectionClass($factory);

        try {
            if ($reflection->hasMethod('createForNewestSupportedVersion')) {
                $created = $reflection->getMethod('createForNewestSupportedVersion')->invoke($factory);
            } else {
                $created = $reflection->getMethod('create')->invoke($factory, constant(ParserFactory::class . '::PREFER_PHP7'));
            }
        } catch (ReflectionException $exception) {
            throw new RuntimeException('The installed nikic/php-parser version exposes no supported parser factory method.', 0, $exception);
        }

        if (!$created instanceof Parser) {
            throw new RuntimeException('The installed nikic/php-parser version produced no parser instance.');
        }

        return $created;
    }
}
