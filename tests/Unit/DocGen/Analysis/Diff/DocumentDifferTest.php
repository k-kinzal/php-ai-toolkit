<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;
use PhpAiToolkit\DocGen\Analysis\Diff\DocumentDiffer;
use PhpAiToolkit\DocGen\Analysis\Model\MarkdownDoc;
use PhpAiToolkit\DocGen\Analysis\ProjectModel;
use PhpAiToolkit\DocGen\Analysis\Reference\HierarchyIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\SymbolTable;
use PhpAiToolkit\DocGen\Analysis\Reference\TestCaseIndex;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageIndex;
use PhpAiToolkit\DocGen\Package\PackageGraph;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocumentDiffer::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(MarkdownDoc::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(UsageIndex::class)]
final class DocumentDifferTest extends TestCase
{
    public function testMergeKeepsEveryDocumentOfBothRevisionsAndMarksIt(): void
    {
        $headRoot = sys_get_temp_dir() . '/docgen-docs-head-' . bin2hex(random_bytes(4));
        $baseRoot = sys_get_temp_dir() . '/docgen-docs-base-' . bin2hex(random_bytes(4));
        mkdir($headRoot . '/docs', 0777, true);
        mkdir($baseRoot . '/docs', 0777, true);
        file_put_contents($headRoot . '/docs/guide.md', "# Guide\n\nNew wording.\n");
        file_put_contents($baseRoot . '/docs/guide.md', "# Guide\n\nOld wording.\n");
        file_put_contents($headRoot . '/docs/fresh.md', "# Fresh\n");
        file_put_contents($baseRoot . '/docs/gone.md', "# Gone\n");
        $index = new DiffIndex('main', 'HEAD', $baseRoot);
        $keys = $index->keys();

        $documents = (new DocumentDiffer())->merge(
            new ProjectModel('Demo', $baseRoot, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [
                new MarkdownDoc('demo/app', 'docs/guide.md', 'docs/guide.md', 'Guide'),
                new MarkdownDoc('demo/app', 'docs/gone.md', 'docs/gone.md', 'Gone'),
            ]),
            new ProjectModel('Demo', $headRoot, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [
                new MarkdownDoc('demo/app', 'docs/guide.md', 'docs/guide.md', 'Guide'),
                new MarkdownDoc('demo/app', 'docs/fresh.md', 'docs/fresh.md', 'Fresh'),
            ]),
            $index,
        );

        self::assertCount(3, $documents);
        self::assertSame('docs/gone.md', $documents[2]->path);
        self::assertSame(DiffStatus::MODIFIED, $index->status($keys->document('demo/app', 'docs/guide.md')));
        self::assertSame(DiffStatus::ADDED, $index->status($keys->document('demo/app', 'docs/fresh.md')));
        self::assertSame(DiffStatus::REMOVED, $index->status($keys->document('demo/app', 'docs/gone.md')));
    }

    public function testMergeReportsAnUntouchedDocumentAsUnchanged(): void
    {
        $headRoot = sys_get_temp_dir() . '/docgen-docs-head-' . bin2hex(random_bytes(4));
        $baseRoot = sys_get_temp_dir() . '/docgen-docs-base-' . bin2hex(random_bytes(4));
        mkdir($headRoot . '/docs', 0777, true);
        mkdir($baseRoot . '/docs', 0777, true);
        file_put_contents($headRoot . '/docs/guide.md', "# Guide\n");
        file_put_contents($baseRoot . '/docs/guide.md', "# Guide\r\n");
        $index = new DiffIndex('main', 'HEAD', $baseRoot);
        $document = new MarkdownDoc('demo/app', 'docs/guide.md', 'docs/guide.md', 'Guide');

        (new DocumentDiffer())->merge(
            new ProjectModel('Demo', $baseRoot, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]),
            new ProjectModel('Demo', $headRoot, [], new PackageGraph([]), [], [], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, [], [$document]),
            $index,
        );

        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->document('demo/app', 'docs/guide.md')));
    }

    public function testStatusOfTreatsAnUnreadableRevisionAsAChange(): void
    {
        $differ = new DocumentDiffer();
        $document = new MarkdownDoc('demo/app', 'docs/guide.md', 'docs/guide.md', 'Guide');

        self::assertSame(DiffStatus::ADDED, $differ->statusOf(null, $document, '/tmp/missing', new DiffIndex('main', 'HEAD')));
        self::assertSame(DiffStatus::MODIFIED, $differ->statusOf($document, $document, '/tmp/missing', new DiffIndex('main', 'HEAD')));
    }

    public function testContentsReadsAFileOrReportsThatItCannot(): void
    {
        $root = sys_get_temp_dir() . '/docgen-docs-' . bin2hex(random_bytes(4));
        mkdir($root, 0777, true);
        file_put_contents($root . '/guide.md', '# Guide');

        self::assertSame('# Guide', (new DocumentDiffer())->contents($root . '/guide.md'));
        self::assertNull((new DocumentDiffer())->contents($root . '/missing.md'));
    }

    public function testNormalizedComparesDocumentsWithoutTheirLineEndings(): void
    {
        $differ = new DocumentDiffer();

        self::assertSame("one\ntwo", $differ->normalized("one\r\ntwo"));
        self::assertSame($differ->normalized("one\ntwo"), $differ->normalized("one\r\ntwo"));
    }
}
