<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\TargetPath
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Execution\TargetPath::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class TargetPathTest extends TestCase
{
    public function testResolveRejectsEscapingRoot(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Execution\TargetPath())->resolve(sys_get_temp_dir(), '../outside.json');
    }

    public function testRejectsSymlinkedTargets(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/actual.json', '{}');
        symlink($root . '/actual.json', $root . '/linked.json');
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Execution\TargetPath())->resolve($root, 'linked.json');
    }

}
