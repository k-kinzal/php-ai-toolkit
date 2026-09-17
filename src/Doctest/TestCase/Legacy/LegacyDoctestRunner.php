<?php

declare(strict_types=1);

namespace Toolkit\Doctest\TestCase\Legacy;

use Generator;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Executor\ExampleExecutor;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Parser\ExampleExtractor;
use Toolkit\Doctest\Scanner\FileScanner;
use Toolkit\Doctest\Scanner\SourceScanner;

/**
 * Base class for running doctests as PHPUnit 9 tests.
 *
 * PHPUnit 9 reads test metadata from doc-comments rather than from attributes,
 * so the provider is bound with an annotation here and with an attribute in
 * DoctestRunner. Projects on PHPUnit 10 or later should extend that class:
 * doc-comment metadata was removed in PHPUnit 12.
 *
 * PHPUnit 9 also instantiates its hook extensions only after the test suite,
 * and with it every data provider, has been built, so no extension can hand a
 * suite its parameters there. LegacyDoctestSuite, the concrete suite the
 * package ships for PHPUnit 9, reads them from the environment variables the
 * php element of phpunit.xml sets instead. Extending this class directly is a
 * custom integration point, not the setup path.
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
     * A source set without examples yields one placeholder instead of nothing,
     * the way DoctestRunner does: PHPUnit 9 reports a provider that returns no
     * data as a skipped test, and a strict configuration fails the run on a
     * skip.
     *
     * @return Generator<string, array{?Example}>
     */
    public static function doctestProvider(): Generator
    {
        $config = static::configure();
        $fileScanner = new FileScanner($config);
        $sourceScanner = new SourceScanner();
        $extractor = new ExampleExtractor();
        $found = false;

        foreach ($fileScanner->scan() as $filePath) {
            foreach ($sourceScanner->scanFile($filePath) as $target) {
                foreach ($extractor->extract($target) as $example) {
                    $found = true;
                    yield $example->getName() => [$example];
                }
            }
        }

        if (!$found) {
            yield 'No doctest examples found' => [null];
        }
    }

    /**
     * Tests a docblock example.
     *
     * An example exercises whatever code it documents, so it declares no
     * coverage target of its own: it is a check on the documentation, not a
     * unit test of one class.
     *
     * @param ?Example $example the example to test, or null when none were discovered
     *
     * @dataProvider doctestProvider
     *
     * @coversNothing
     *
     * @medium
     */
    public function testDocblockExample(?Example $example): void
    {
        if ($example === null) {
            $this->addToAssertionCount(1);

            return;
        }

        if (self::$executor === null) {
            self::$executor = new ExampleExecutor(static::configure()->getBootstrap());
        }

        $result = self::$executor->execute($example);

        self::assertTrue($result->passed, $result->getErrorMessage());
    }
}
