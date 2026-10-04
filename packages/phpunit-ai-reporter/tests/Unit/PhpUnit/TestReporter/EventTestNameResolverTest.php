<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use function array_merge;
use function getenv;
use function interface_exists;

use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Fixture\TestReporter\PhpUnitFixtureProcess;

/**
 * @coversNothing
 * @large
 */
#[CoversNothing]
#[Large]
final class EventTestNameResolverTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testResolveReturnsClassQualifiedMethodName(): void
    {
        $environment = getenv();
        unset($environment['PARATEST']);
        $environment = array_merge($environment, ['AI_AGENT' => '1']);

        $result = PhpUnitFixtureProcess::runExtension($environment);

        self::assertNotSame(0, $result->exitCode());
        self::assertStringContainsString('Tests\Fixture\TestReporter\FailingTest::testFails', $result->output());
    }
}
