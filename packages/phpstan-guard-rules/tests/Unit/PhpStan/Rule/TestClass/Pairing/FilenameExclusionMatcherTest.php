<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher
 */
#[CoversClass(FilenameExclusionMatcher::class)]
final class FilenameExclusionMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueForConfiguredPattern(): void
    {
        self::assertTrue((new FilenameExclusionMatcher(['*Interface.php']))->matches('UserInterface.php'));
    }
}
