<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Filesystem;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\Config\ReportConfig;
use PhpAiToolkit\Doctest\Filesystem\DoctestPathResolver;
use PhpAiToolkit\Doctest\Filesystem\PhpFileInclusionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpFileInclusionPolicy::class)]
#[UsesClass(DoctestConfig::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(DoctestPathResolver::class)]
final class PhpFileInclusionPolicyTest extends TestCase
{
    public function testIncludesOnlyNonExcludedPhpFiles(): void
    {
        $config = new DoctestConfig('/app', ['src'], ['src/Generated/*'], null, new ReportConfig('ai', ['path']));
        $policy = new PhpFileInclusionPolicy();

        self::assertTrue($policy->includes($config, '/app/src/Ledger.php'));
        self::assertFalse($policy->includes($config, '/app/src/Ledger.twig'));
        self::assertFalse($policy->includes($config, '/app/src/Generated/Proxy.php'));
    }
}
