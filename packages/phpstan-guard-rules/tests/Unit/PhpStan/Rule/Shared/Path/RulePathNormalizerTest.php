<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared\Path;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 */
#[CoversClass(RulePathNormalizer::class)]
final class RulePathNormalizerTest extends TestCase
{
    public function testNormalizeConvertsBackslashesToSlashes(): void
    {
        self::assertSame('C:/project/src/File.php', (new RulePathNormalizer())->normalize('C:\\project\\src\\File.php'));
    }
}
