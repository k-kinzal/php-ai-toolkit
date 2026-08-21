<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\Doctest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Runs every documented example of a project as its own PHPUnit test.
 *
 * Extend this class in a project's test suite to have the examples in its
 * PHPDoc blocks checked alongside its unit tests. Each example becomes one test
 * case named by its identifier, so a failure names the example that broke and
 * that same identifier re-runs it on its own with "vendor/bin/doctest --filter".
 *
 * This class reads example metadata through PHPUnit attributes and therefore
 * needs PHPUnit 10 or later; on PHPUnit 9 extend LegacyDoctestTestCase instead.
 * Override doctestConfigPath() to point at the project's doctest.yaml. See the
 * doctest documentation for a worked setup.
 */
abstract class DoctestTestCase extends TestCase
{
    private static ?DoctestExampleIndex $index = null;

    /**
     * Returns the doctest configuration this suite runs.
     *
     * Override to point at a configuration file other than the default.
     */
    public static function doctestConfigPath(): string
    {
        return 'doctest.yaml';
    }

    /**
     * Returns the index of the configured examples, built once per configuration.
     */
    public static function doctestIndex(): DoctestExampleIndex
    {
        $configPath = static::doctestConfigPath();
        if (self::$index === null || self::$index->configPath() !== $configPath) {
            self::$index = new DoctestExampleIndex($configPath);
        }

        return self::$index;
    }

    /**
     * Provides one test case per documented example.
     *
     * @return iterable<string, array{string}>
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when the configuration or a scanned file cannot be read
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
     * and is reported as rendered rather than run by the doctest command.
     *
     * @param string $id the identifier of the example to run
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when the documented file cannot be loaded
     */
    #[DataProvider('doctestExampleProvider')]
    public function testDocblockExample(string $id): void
    {
        $result = static::doctestIndex()->run($id);

        self::assertTrue($result->passed(), sprintf("Documented example %s does not hold.\n%s", $id, $result->errorMessage()));
    }
}
