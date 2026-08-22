<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\TestCase\Legacy;

use Generator;
use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Executor\ExampleExecutor;
use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\FileScanner;
use PhpAiToolkit\Doctest\Scanner\SourceScanner;
use PHPUnit\Framework\TestCase;

/**
 * Base class for running doctests as PHPUnit 9 tests.
 *
 * PHPUnit 9 reads test metadata from doc-comments rather than from attributes,
 * so the provider is bound with an annotation here and with an attribute in
 * DoctestRunner. Projects on PHPUnit 10 or later should extend that class:
 * doc-comment metadata was removed in PHPUnit 12.
 *
 * PHPUnit 9 also has no extension API, so a suite on that version implements
 * configure() itself rather than reading parameters from phpunit.xml.
 */
abstract class LegacyDoctestRunner extends TestCase
{
    private static ?ExampleExecutor $executor = null;

    /**
     * Override to provide custom configuration.
     *
     * @return Configuration the configuration for doctest scanning
     */
    abstract public static function configure(): Configuration;

    /**
     * Provides examples as test data.
     *
     * @return Generator<string, array{Example}>
     */
    public static function doctestProvider(): Generator
    {
        $config = static::configure();
        $fileScanner = new FileScanner($config);
        $sourceScanner = new SourceScanner();
        $extractor = new ExampleExtractor();

        foreach ($fileScanner->scan() as $filePath) {
            foreach ($sourceScanner->scanFile($filePath) as $target) {
                foreach ($extractor->extract($target) as $example) {
                    yield $example->getName() => [$example];
                }
            }
        }
    }

    /**
     * Tests a docblock example.
     *
     * An example exercises whatever code it documents, so it declares no
     * coverage target of its own: it is a check on the documentation, not a
     * unit test of one class.
     *
     * @param Example $example the example to test
     *
     * @dataProvider doctestProvider
     *
     * @coversNothing
     *
     * @medium
     */
    public function testDocblockExample(Example $example): void
    {
        if (self::$executor === null) {
            self::$executor = new ExampleExecutor(static::configure()->getBootstrap());
        }

        $result = self::$executor->execute($example);

        self::assertTrue($result->passed, $result->getErrorMessage());
    }
}
