<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Filesystem;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

#[CoversClass(SiteFileWriter::class)]
#[UsesClass(DocGenException::class)]
final class SiteFileWriterTest extends TestCase
{
    public function testWriteCreatesParentDirectoriesAndFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-writer-' . uniqid('', true);

        (new SiteFileWriter())->write($dir . '/site', 'pages/nested/index.html', '<h1>Doc</h1>');

        self::assertSame('<h1>Doc</h1>', file_get_contents($dir . '/site/pages/nested/index.html'));
    }

    public function testWriteOverwritesExistingFile(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-writer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/index.html', 'old');

        (new SiteFileWriter())->write($dir, 'index.html', 'new');

        self::assertSame('new', file_get_contents($dir . '/index.html'));
    }

    #[WithoutErrorHandler]
    public function testWriteRejectsFileBlockingDirectoryCreation(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-writer-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/blocker', '');

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Failed to create output directory: ' . $dir . '/blocker');

        (new SiteFileWriter())->write($dir, 'blocker/index.html', 'x');
    }
}
