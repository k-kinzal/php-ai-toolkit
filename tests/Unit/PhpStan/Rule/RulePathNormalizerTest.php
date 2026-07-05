<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\RulePathNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RulePathNormalizer::class)]
final class RulePathNormalizerTest extends TestCase
{
    public function testNormalizeConvertsBackslashesToSlashes(): void
    {
        self::assertSame('C:/project/src/File.php', (new RulePathNormalizer())->normalize('C:\\project\\src\\File.php'));
    }
}
