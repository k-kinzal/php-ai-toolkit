<?php

declare(strict_types=1);

namespace Tests\Unit\TestReporter;

use PhpStanAiRules\TestReporter\AiTestReporterExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Extension\Extension;

#[CoversClass(AiTestReporterExtension::class)]
final class AiTestReporterExtensionTest extends TestCase
{
    public function testImplementsExtensionInterface(): void
    {
        $extension = new AiTestReporterExtension();

        self::assertInstanceOf(Extension::class, $extension);
    }

    public function testBootstrapAcceptsCustomWriter(): void
    {
        $output = [];
        $extension = new AiTestReporterExtension(static function (string $message) use (&$output): void {
            $output[] = $message;
        });

        self::assertInstanceOf(Extension::class, $extension);
    }
}
