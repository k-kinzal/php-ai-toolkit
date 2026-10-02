<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Configuration;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\ParameterCollection;

use function putenv;

use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Configuration\ConfigurationLoader;

/**
 * @covers \Toolkit\Doctest\Configuration\ConfigurationLoader
 * @uses \Toolkit\Doctest\Configuration\Configuration
 */
#[CoversClass(ConfigurationLoader::class)]
#[UsesClass(Configuration::class)]
final class ConfigurationLoaderTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        putenv('DOCTEST_DIRECTORIES');
        putenv('DOCTEST_FILES');
        putenv('DOCTEST_EXCLUDE');
        putenv('DOCTEST_BOOTSTRAP');
        putenv('DOCTEST_ENABLED');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('DOCTEST_DIRECTORIES');
        putenv('DOCTEST_FILES');
        putenv('DOCTEST_EXCLUDE');
        putenv('DOCTEST_BOOTSTRAP');
        putenv('DOCTEST_ENABLED');
        parent::tearDown();
    }

    public function testFromParametersResolvesPathsAgainstTheBasePath(): void
    {
        $parameters = ParameterCollection::fromArray(['directories' => 'src, lib', 'files' => 'helpers.php']);

        $config = ConfigurationLoader::fromParameters($parameters, '/app');

        self::assertSame(['/app/src', '/app/lib'], $config->getDirectories());
        self::assertSame(['/app/helpers.php'], $config->getFiles());
    }

    public function testFromParametersReadsExclusionsBootstrapAndEnabled(): void
    {
        $parameters = ParameterCollection::fromArray([
            'exclude' => '*Test.php, *Interface.php',
            'bootstrap' => 'tests/bootstrap.php',
            'enabled' => 'false',
        ]);

        $config = ConfigurationLoader::fromParameters($parameters, '/app');

        self::assertSame(['*Test.php', '*Interface.php'], $config->getExcludePatterns());
        self::assertSame('/app/tests/bootstrap.php', $config->getBootstrap());
        self::assertFalse($config->isEnabled());
    }

    public function testFromParametersDefaultsToAnEmptyEnabledConfiguration(): void
    {
        $config = ConfigurationLoader::fromParameters(ParameterCollection::fromArray([]), '/app');

        self::assertSame([], $config->getDirectories());
        self::assertSame([], $config->getFiles());
        self::assertSame([], $config->getExcludePatterns());
        self::assertNull($config->getBootstrap());
        self::assertTrue($config->isEnabled());
    }

    public function testFromEnvironmentReadsEveryPrefixedVariable(): void
    {
        putenv('DOCTEST_DIRECTORIES=src, lib');
        putenv('DOCTEST_FILES=helpers.php');
        putenv('DOCTEST_EXCLUDE=*Test.php, *Interface.php');
        putenv('DOCTEST_BOOTSTRAP=tests/bootstrap.php');
        putenv('DOCTEST_ENABLED=false');

        $config = ConfigurationLoader::fromEnvironment('/app');

        self::assertSame(['/app/src', '/app/lib'], $config->getDirectories());
        self::assertSame(['/app/helpers.php'], $config->getFiles());
        self::assertSame(['*Test.php', '*Interface.php'], $config->getExcludePatterns());
        self::assertSame('/app/tests/bootstrap.php', $config->getBootstrap());
        self::assertFalse($config->isEnabled());
    }

    public function testFromEnvironmentDefaultsToAnEmptyEnabledConfiguration(): void
    {
        $config = ConfigurationLoader::fromEnvironment('/app');

        self::assertSame([], $config->getDirectories());
        self::assertSame([], $config->getFiles());
        self::assertSame([], $config->getExcludePatterns());
        self::assertNull($config->getBootstrap());
        self::assertTrue($config->isEnabled());
    }

    public function testFromValuesReadsEveryParameter(): void
    {
        $config = ConfigurationLoader::fromValues([
            'directories' => 'src',
            'files' => 'helpers.php',
            'exclude' => '*Test.php',
            'bootstrap' => '/opt/bootstrap.php',
            'enabled' => 'true',
        ], '/app');

        self::assertSame(['/app/src'], $config->getDirectories());
        self::assertSame(['/app/helpers.php'], $config->getFiles());
        self::assertSame(['*Test.php'], $config->getExcludePatterns());
        self::assertSame('/opt/bootstrap.php', $config->getBootstrap());
        self::assertTrue($config->isEnabled());
    }

    public function testFromValuesTreatsAnUnrecognisedEnabledValueAsOff(): void
    {
        $config = ConfigurationLoader::fromValues(['enabled' => 'maybe'], '/app');

        self::assertFalse($config->isEnabled());
    }

    public function testPathsReturnsAnEmptyListForAnAbsentParameter(): void
    {
        self::assertSame([], ConfigurationLoader::paths(null, '/app'));
    }

    public function testPathsResolvesEachEntryAgainstTheBasePath(): void
    {
        self::assertSame(['/app/src', '/lib'], ConfigurationLoader::paths('src, /lib', '/app'));
    }

    public function testPatternsTrimsAndDropsEmptyEntries(): void
    {
        self::assertSame(['*Test.php', '*Stub.php'], ConfigurationLoader::patterns(' *Test.php , , *Stub.php '));
    }

    public function testPatternsReturnsAnEmptyListForAnAbsentParameter(): void
    {
        self::assertSame([], ConfigurationLoader::patterns(null));
    }
}
