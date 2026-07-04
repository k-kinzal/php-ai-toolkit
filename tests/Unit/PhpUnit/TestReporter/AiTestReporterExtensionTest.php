<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\AiTestReporterExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Registry;

use function putenv;

#[CoversClass(AiTestReporterExtension::class)]
final class AiTestReporterExtensionTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        putenv('PARATEST');
    }

    #[Override]
    protected function tearDown(): void
    {
        putenv('PARATEST');
        parent::tearDown();
    }

    public function testBootstrapSkipsParatestWorkerOutputReplacement(): void
    {
        putenv('PARATEST=1');
        $facade = new Facade();
        $extension = new AiTestReporterExtension();

        $extension->bootstrap(Registry::get(), $facade, ParameterCollection::fromArray([]));

        self::assertFalse($facade->replacesProgressOutput());
        self::assertFalse($facade->replacesResultOutput());
    }

    public function testBootstrapSkipsCustomWriterInParatestWorker(): void
    {
        putenv('PARATEST=1');
        $output = [];
        $extension = new AiTestReporterExtension(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        $extension->bootstrap(Registry::get(), new Facade(), ParameterCollection::fromArray([]));

        self::assertSame([], $output);
    }
}
