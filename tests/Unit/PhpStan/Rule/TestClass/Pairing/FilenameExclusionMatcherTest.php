<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher
 */
#[CoversClass(FilenameExclusionMatcher::class)]
final class FilenameExclusionMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueForConfiguredPattern(): void
    {
        self::assertTrue((new FilenameExclusionMatcher(['*Interface.php']))->matches('UserInterface.php'));
    }
}
