<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\FilenameExclusionMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FilenameExclusionMatcher::class)]
final class FilenameExclusionMatcherTest extends TestCase
{
    public function testMatchesReturnsTrueForConfiguredPattern(): void
    {
        self::assertTrue((new FilenameExclusionMatcher(['*Interface.php']))->matches('UserInterface.php'));
    }
}
