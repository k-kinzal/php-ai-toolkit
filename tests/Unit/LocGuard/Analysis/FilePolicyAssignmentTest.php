<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\FilePolicyAssignment;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;

#[CoversClass(FilePolicyAssignment::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(PolicyConfig::class)]
final class FilePolicyAssignmentTest extends TestCase
{
    public function testStoresSelectedPolicyAndRule(): void
    {
        $policy = new PolicyConfig('native-api', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $assignment = new FilePolicyAssignment('/project/src/Native.php', 'src/Native.php', $policy, 'native');

        self::assertSame('/project/src/Native.php', $assignment->path);
        self::assertSame('src/Native.php', $assignment->relativePath);
        self::assertSame($policy, $assignment->policy);
        self::assertSame('native', $assignment->rule);
    }
}
