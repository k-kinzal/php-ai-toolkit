<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\TargetPath
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Execution\TargetPath::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class TargetPathTest extends TestCase
{
    public function testResolveRejectsEscapingRoot(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Execution\TargetPath())->resolve(sys_get_temp_dir(), '../outside.json');
    }

    public function testRejectsSymlinkedTargets(): void
    {
        $root = sys_get_temp_dir() . '/guard-path-' . uniqid();
        mkdir($root);
        file_put_contents($root . '/actual.json', '{}');
        symlink($root . '/actual.json', $root . '/linked.json');
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Execution\TargetPath())->resolve($root, 'linked.json');
    }

}
