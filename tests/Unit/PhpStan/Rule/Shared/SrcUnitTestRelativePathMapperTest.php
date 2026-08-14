<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\SrcUnitTestRelativePathMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SrcUnitTestRelativePathMapper::class)]
final class SrcUnitTestRelativePathMapperTest extends TestCase
{
    public function testToUnitTestRelativePathAddsTestSuffix(): void
    {
        self::assertSame('Domain/UserTest.php', (new SrcUnitTestRelativePathMapper())->toUnitTestRelativePath('Domain/User.php'));
    }

    public function testToSourceRelativePathRemovesTestSuffix(): void
    {
        self::assertSame('Domain/User.php', (new SrcUnitTestRelativePathMapper())->toSourceRelativePath('Domain/UserTest.php'));
    }
}
