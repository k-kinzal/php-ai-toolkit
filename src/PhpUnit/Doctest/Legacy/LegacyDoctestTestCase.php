<?php

declare(strict_types=1);

namespace PhpAiToolkit\PhpUnit\Doctest\Legacy;

use PhpAiToolkit\PhpUnit\Doctest\DoctestExampleIndex;
use PHPUnit\Framework\TestCase;

use function sprintf;

/**
 * Runs every documented example of a project as its own PHPUnit 9 test.
 *
 * PHPUnit 9 reads test metadata from doc-comments rather than from attributes,
 * so the provider is bound with an annotation here and with an attribute in
 * DoctestTestCase. Projects on PHPUnit 10 or later should extend that class:
 * doc-comment metadata was removed in PHPUnit 12.
 */
abstract class LegacyDoctestTestCase extends TestCase
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
     * @dataProvider doctestExampleProvider
     *
     * @throws \PhpAiToolkit\Doctest\DoctestException when the documented file cannot be loaded
     */
    public function testDocblockExample(string $id): void
    {
        $result = static::doctestIndex()->run($id);

        self::assertTrue($result->passed(), sprintf("Documented example %s does not hold.\n%s", $id, $result->errorMessage()));
    }
}
