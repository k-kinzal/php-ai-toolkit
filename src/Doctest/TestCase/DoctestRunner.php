<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\TestCase;

use PhpAiToolkit\Doctest\Configuration\Configuration;
use PhpAiToolkit\Doctest\Executor\ExampleExecutor;
use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\FileScanner;
use PhpAiToolkit\Doctest\Scanner\SourceScanner;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Base class for running doctests as PHPUnit tests.
 *
 * Extend this class and implement the configure() method to run doctests
 * for your project. Each example found becomes one PHPUnit test case.
 *
 * This class binds its data provider with a PHPUnit attribute and therefore
 * needs PHPUnit 10 or later. On PHPUnit 9 extend
 * PhpAiToolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner, which binds the
 * same provider with a doc-comment annotation.
 *
 * This is the only entry point the port carries. k-kinzal/doctest-php also
 * ships a DoctestCase wrapping one example, which nothing there uses and which
 * cannot work here: it names itself through the PHPUnit constructor, and
 * PHPUnit 10 made TestCase::__construct() final.
 */
abstract class DoctestRunner extends TestCase
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
     * @return iterable<string, array{Example}>
     */
    public static function doctestProvider(): iterable
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
     */
    #[Test]
    #[DataProvider('doctestProvider')]
    #[CoversNothing]
    public function testDocblockExample(Example $example): void
    {
        if (self::$executor === null) {
            self::$executor = new ExampleExecutor(static::configure()->getBootstrap());
        }

        $result = self::$executor->execute($example);

        self::assertTrue($result->passed, $result->getErrorMessage());
    }
}
