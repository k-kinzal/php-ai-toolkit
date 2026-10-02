<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cache\ToolkitFingerprint;

/**
 * @covers \Toolkit\DocGen\Cache\ToolkitFingerprint
 */
#[CoversClass(ToolkitFingerprint::class)]
final class ToolkitFingerprintTest extends TestCase
{
    public function testValueIsTheSameForTheSameInstallation(): void
    {
        self::assertSame((new ToolkitFingerprint())->value(), (new ToolkitFingerprint())->value());
        self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', (new ToolkitFingerprint())->value());
    }

    public function testVersionOfNamesTheInstalledVersionAndItsReference(): void
    {
        $version = (new ToolkitFingerprint())->versionOf('nikic/php-parser');

        self::assertMatchesRegularExpression('/^.+@.*$/', $version);
        self::assertStringContainsString('@', $version);
    }

    public function testVersionOfReportsNothingForAPackageThatIsNotInstalled(): void
    {
        self::assertSame('', (new ToolkitFingerprint())->versionOf('acme/not-installed'));
    }

    public function testPackagesNameTheToolkitAndTheLibrariesItReadsWith(): void
    {
        self::assertContains('k-kinzal/php-ai-toolkit', ToolkitFingerprint::PACKAGES);
        self::assertContains('nikic/php-parser', ToolkitFingerprint::PACKAGES);
        self::assertContains('phpstan/phpdoc-parser', ToolkitFingerprint::PACKAGES);
    }
}
