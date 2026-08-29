<?php

declare(strict_types=1);

namespace Toolkit\Doctest\TestCase;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Toolkit\Doctest\Configuration\Configuration;
use Toolkit\Doctest\Executor\ExampleExecutor;
use Toolkit\Doctest\Parser\Example;
use Toolkit\Doctest\Parser\ExampleExtractor;
use Toolkit\Doctest\Scanner\FileScanner;
use Toolkit\Doctest\Scanner\SourceScanner;

/**
 * Base class behind the toolkit's PHPUnit doctest suite.
 *
 * A project on PHPUnit 10 or later normally does not extend this class. It
 * points its PHPUnit configuration at the package's DoctestSuite.php and
 * supplies scan paths to DoctestExtension. Extending this runner is a custom
 * integration point, not the setup path. Each example found becomes one
 * PHPUnit test case.
 *
 * This class binds its data provider with a PHPUnit attribute and therefore
 * needs PHPUnit 10 or later. On PHPUnit 9 extend
 * Toolkit\Doctest\TestCase\Legacy\LegacyDoctestRunner, which binds the
 * same provider with a doc-comment annotation.
 *
 * An example exercises whatever code it documents, so a suite declares no
 * coverage target of its own: mark the subclass #[CoversNothing], the way
 * DoctestSuite is. The attribute has to sit on the concrete class, because
 * PHPUnit reads class metadata from the class it runs rather than from what
 * that class extends, and PHPUnit 12 deprecated carrying it on the test method.
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
     * @param ?Example $example the example to test, or null when none were discovered
     */
    #[Test]
    #[DataProvider('doctestProvider')]
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
