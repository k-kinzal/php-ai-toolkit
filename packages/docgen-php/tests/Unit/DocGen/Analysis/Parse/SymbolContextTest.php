<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Parse\SymbolContext;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\SymbolContext
 */
#[CoversClass(SymbolContext::class)]
final class SymbolContextTest extends TestCase
{
    public function testStoresFileContext(): void
    {
        $context = new SymbolContext('Demo', ['countable' => 'Countable'], 'demo/pkg', 'src/Sample.php', true);

        self::assertSame('Demo', $context->namespace);
        self::assertSame(['countable' => 'Countable'], $context->useMap);
        self::assertSame('demo/pkg', $context->packageName);
        self::assertSame('src/Sample.php', $context->file);
        self::assertTrue($context->isDev);
    }
}
