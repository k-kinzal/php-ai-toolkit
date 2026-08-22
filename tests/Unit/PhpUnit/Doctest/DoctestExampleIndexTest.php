<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\Doctest;

use PhpAiToolkit\Doctest\Config\DoctestConfig;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\PhpUnit\Doctest\DoctestExampleIndex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestExampleIndex::class)]
#[Medium]
final class DoctestExampleIndexTest extends TestCase
{
    public function testConfigReturnsTheConfigurationItWasBuiltFor(): void
    {
        $config = new DoctestConfig(__DIR__ . '/../../../Fixture/Doctest/project', ['src'], ['src/Nested/*']);

        self::assertSame($config, (new DoctestExampleIndex($config))->config());
    }

    public function testExamplesDiscoversTheProjectExamplesOnce(): void
    {
        $index = new DoctestExampleIndex(new DoctestConfig(__DIR__ . '/../../../Fixture/Doctest/project', ['src'], ['src/Nested/*']));
        $examples = $index->examples();

        self::assertCount(6, $examples);
        self::assertSame($examples, $index->examples());
    }

    public function testExampleFindsAnExampleByItsIdentifier(): void
    {
        $index = new DoctestExampleIndex(new DoctestConfig(__DIR__ . '/../../../Fixture/Doctest/project', ['src'], ['src/Nested/*']));

        self::assertSame(
            'Tests\Fixture\Doctest\Project\Calculator::add()#2',
            $index->example('Tests\Fixture\Doctest\Project\Calculator::add()#2')->id(),
        );
    }

    public function testExampleRejectsAnUnknownIdentifier(): void
    {
        $index = new DoctestExampleIndex(new DoctestConfig(__DIR__ . '/../../../Fixture/Doctest/project', ['src'], ['src/Nested/*']));

        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('No documented example is identified by "Missing#1".');

        $index->example('Missing#1');
    }

    public function testRunExecutesTheNamedExample(): void
    {
        $index = new DoctestExampleIndex(new DoctestConfig(__DIR__ . '/../../../Fixture/Doctest/project', ['src'], ['src/Nested/*']));

        $result = $index->run('Tests\Fixture\Doctest\Project\Calculator::add()#1');

        self::assertTrue($result->passed());
        self::assertFalse($result->skipped);
    }
}
