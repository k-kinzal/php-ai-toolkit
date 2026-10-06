<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Loc\Assignment;

use Guard\Config\Loc\LimitConfig;
use Guard\Config\Loc\Policy\PolicyConfig;
use Guard\Policy\Loc\Assignment\FilePolicyAssignment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Loc\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 */
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
