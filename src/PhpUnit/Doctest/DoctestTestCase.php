<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\Doctest;

use function getcwd;

use PhpAiToolkit\Doctest\Analysis\PhpUnitFilter;
use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Runs every documented example of a project as its own PHPUnit test.
 *
 * Extend this class in a project's test suite to have the examples in its
 * PHPDoc blocks checked alongside its unit tests. Each example becomes one test
 * case named by its identifier, so a failure names the example that broke and
 * PHPUnit's own --filter re-runs that one example.
 *
 * The defaults scan "src" under the working directory, which is the project
 * root PHPUnit runs from, so most projects need no configuration at all:
 * declaring a subclass is the whole setup. Override doctestPaths(),
 * doctestExcludes(), doctestBootstrap(), or doctestRoot() to change what is
 * scanned.
 *
 * This class reads its metadata from PHPUnit attributes and therefore needs
 * PHPUnit 10 or later; on PHPUnit 9 extend LegacyDoctestTestCase instead.
 */
abstract class DoctestTestCase extends TestCase
{
    /** @var array<string, DoctestExampleIndex> */
    private static array $indexes = [];

    /**
     * Returns the directory the scanned paths are relative to.
     *
     * Override when the suite runs from somewhere other than the project root.
     */
    public static function doctestRoot(): string
    {
        $workingDirectory = getcwd();

        return $workingDirectory === false ? '.' : $workingDirectory;
    }

    /**
     * Returns the files and directories scanned for documented examples.
     *
     * @return list<string>
     */
    public static function doctestPaths(): array
    {
        return ['src'];
    }

    /**
     * Returns fnmatch globs of project-relative paths to leave unscanned.
     *
     * @return list<string>
     */
    public static function doctestExcludes(): array
    {
        return [];
    }

    /**
     * Returns a file to include once before the first example runs.
     *
     * A project whose sources are autoloaded needs none, because PHPUnit has
     * already loaded the autoloader by the time the examples run.
     */
    public static function doctestBootstrap(): ?string
    {
        return null;
    }

    /**
     * Returns the configuration assembled from the overridable settings.
     */
    public static function doctestConfig(): DoctestConfig
    {
        return new DoctestConfig(static::doctestRoot(), static::doctestPaths(), static::doctestExcludes(), static::doctestBootstrap());
    }

    /**
     * Returns the index of the configured examples, built once per subclass.
     */
    public static function doctestIndex(): DoctestExampleIndex
    {
        $suite = static::class;
        if (!isset(self::$indexes[$suite])) {
            self::$indexes[$suite] = new DoctestExampleIndex(static::doctestConfig());
        }

        return self::$indexes[$suite];
    }

    /**
     * Provides one test case per documented example.
     *
     * @return iterable<string, array{string}>
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when a configured path is missing or a file cannot be read
     */
    public static function doctestExampleProvider(): iterable
    {
        foreach (static::doctestIndex()->examples() as $example) {
            yield sprintf('%s [%s]', $example->name(), $example->id()) => [$example->id()];
        }
    }

    /**
     * Checks that one documented example still holds.
     *
     * A display-only example carries no assertion to break, so it passes here
     * and is only rendered on the documentation site.
     *
     * @param string $id the identifier of the example to run
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when the documented file cannot be loaded
     */
    #[DataProvider('doctestExampleProvider')]
    public function testDocblockExample(string $id): void
    {
        $result = static::doctestIndex()->run($id);

        self::assertTrue($result->passed(), sprintf(
            "Documented example %s does not hold.\n%s\n\nRe-run this example on its own with:\n%s",
            $id,
            $result->errorMessage(),
            (new PhpUnitFilter())->command($id),
        ));
    }
}
