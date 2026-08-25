<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\VisibilityRuleErrorBuilder;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\VisibilityRuleErrorBuilder
 */
#[CoversClass(VisibilityRuleErrorBuilder::class)]
final class VisibilityRuleErrorBuilderTest extends TestCase
{
    public function testBuildPreservesLocationIdentifierAndSymbolMetadata(): void
    {
        $error = (new VisibilityRuleErrorBuilder())->build([
            'file' => __FILE__,
            'line' => 8,
            'identifier' => 'customRules.visibilityInvalidScope',
            'symbol' => 'App\Order',
            'message' => 'Fix the scope.',
        ]);

        self::assertSame(__FILE__, $error->getFile());
        self::assertSame(8, $error->getLine());
        self::assertSame('customRules.visibilityInvalidScope', $error->getIdentifier());
        self::assertSame(['symbol' => 'App\Order'], $error->getMetadata());
    }
}
