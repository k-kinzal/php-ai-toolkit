<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use Guard\Cli\BaselineFile;
use Guard\Policy\PolicyException;
use Guard\Reporting\Baseline;
use JsonException;
use Tests\Support\Project;

/**
 * @covers \Guard\Cli\BaselineFile
 * @uses \Guard\Reporting\Baseline
 * @uses \Guard\Config\Schema
 * @uses \Guard\Policy\PolicyException
 */
#[\PHPUnit\Framework\Attributes\CoversClass(BaselineFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Baseline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
final class BaselineFileTest extends \PHPUnit\Framework\TestCase
{
    public function testPathResolvesFromTheSelectedPolicyDirectory(): void
    {
        $file = new BaselineFile();
        self::assertSame('/project/config/guard-baseline.json', $file->path('/project/config/guard.yaml', null));
        self::assertSame('/project/config/custom.json', $file->path('/project/config/guard.yaml', 'custom.json'));
        self::assertSame('/tmp/custom.json', $file->path('/project/config/guard.yaml', '/tmp/custom.json'));
        $this->expectException(PolicyException::class);
        $file->path('/project/guard.yaml', ' ');
    }

    public function testReadRejectsAnExplicitMissingFile(): void
    {
        $this->expectException(PolicyException::class);
        (new BaselineFile())->read('/guard-missing-' . uniqid() . '/baseline.json');
    }

    /**
     * @throws JsonException
     */
    public function testWriteCreatesAndUpdatesOnlyBaselinesWithoutTemporaryFiles(): void
    {
        $project = new Project();
        try {
            $file = new BaselineFile();
            $path = $project->root . '/guard-baseline.json';
            $json = (new Baseline())->capture([]);
            $file->write($path, $json);
            self::assertSame($json, $file->read($path));
            $file->write($path, $json);
            self::assertSame(['guard-baseline.json' => $json], $project->files());
        } finally {
            $project->remove();
        }
    }

    /**
     * @throws JsonException
     */
    public function testWriteRefusesToOverwriteAnUnrelatedJsonFile(): void
    {
        $project = new Project(['composer.json' => '{"name":"example/project"}']);
        try {
            $this->expectException(PolicyException::class);
            (new BaselineFile())->write($project->root . '/composer.json', (new Baseline())->capture([]));
        } finally {
            self::assertSame('{"name":"example/project"}', file_get_contents($project->root . '/composer.json'));
            $project->remove();
        }
    }

    public function testValidateTargetRejectsSymlinksWithoutChangingTheirTargets(): void
    {
        $project = new Project(['real.json' => '{"version":1,"entries":[]}']);
        try {
            symlink($project->root . '/real.json', $project->root . '/link.json');
            $this->expectException(PolicyException::class);
            (new BaselineFile())->validateTarget($project->root . '/link.json');
        } finally {
            self::assertSame('{"version":1,"entries":[]}', file_get_contents($project->root . '/real.json'));
            $project->remove();
        }
    }

    public function testCurrentSeesCreationAndDeletionBetweenReads(): void
    {
        $project = new Project();
        try {
            $file = new BaselineFile();
            $path = $project->root . '/baseline.json';
            self::assertNull($file->current($path));
            $project->write('baseline.json', 'first');
            self::assertSame('first', $file->current($path));
            unlink($path);
            self::assertNull($file->current($path));
        } finally {
            $project->remove();
        }
    }
    public function testValidateNamesTheBrokenFileAndTheRecoveryAction(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Invalid baseline project/custom.json: Syntax error Correct this file');
        (new BaselineFile())->validate('{', 'project/custom.json');
    }
}
