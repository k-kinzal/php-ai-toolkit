<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Parse;

use PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpParserBridge::class)]
#[UsesClass(ScopeGuardException::class)]
final class PhpParserBridgeTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testParserParsesPhpSource(): void
    {
        self::assertCount(1, (new PhpParserBridge())->parser()->parse('<?php class Order {}') ?? []);
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParserIsMemoized(): void
    {
        $bridge = new PhpParserBridge();

        self::assertSame($bridge->parser(), $bridge->parser());
    }
}
