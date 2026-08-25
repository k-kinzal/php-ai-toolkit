<?php

declare(strict_types=1);

namespace Toolkit\ScopeGuard\Analysis\Parse;

use function constant;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use Toolkit\ScopeGuard\ScopeGuardException;

/**
 * Creates a PHP parser for both nikic/php-parser 4 and 5.
 *
 * The two major versions expose different factory methods, so the factory is
 * inspected with reflection and the available method is invoked. This keeps the
 * bridge valid for either major without naming a method only one of them ships.
 *
 * @visibility parent
 */
final class PhpParserBridge
{
    private ?Parser $parser = null;

    /**
     * Returns a memoized parser for the newest supported PHP version.
     *
     * @throws ScopeGuardException when the parser factory exposes no supported creation method or produces no parser
     */
    public function parser(): Parser
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
        } catch (ReflectionException $exception) {
            throw new ScopeGuardException('The installed nikic/php-parser version exposes no supported parser factory method.', 0, $exception);
        }

        if (!$created instanceof Parser) {
            throw new ScopeGuardException('The installed nikic/php-parser version produced no parser instance.');
        }

        $this->parser = $created;

        return $created;
    }
}
