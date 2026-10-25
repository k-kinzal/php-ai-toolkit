<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use Guard\Diagnostic\Finding;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\MarkdownLocalLinks;
use Guard\Policy\Plan;
use Guard\Structure\Markdown\Link\LinkList;
use JsonException;
use Nette\Neon\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\MarkdownLocalLinks
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\Link\LinkList
 */
#[CoversClass(MarkdownLocalLinks::class)]
#[UsesClass(Entry::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Context::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Finding::class)]
#[UsesClass(LinkList::class)]
final class MarkdownLocalLinksTest extends TestCase
{
    public function testInputsSelectsOnlyConfiguredMarkdownSources(): void
    {
        $inputs = (new MarkdownLocalLinks(['docs/*.md'], ['docs/draft.md']))->inputs(new Context('/project', '/project/guard.yaml', false));
        self::assertSame(['docs/*.md'], $inputs['documents']->selection->paths);
        self::assertSame(['docs/draft.md'], $inputs['documents']->selection->exclude);
        self::assertSame('markdown.links', $inputs['documents']->structure);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testEvaluateReportsMissingTargetsAndPreservesExcludedTargets(): void
    {
        $links = new LinkList([
            ['url' => 'absent.md#intro', 'path' => 'absent.md', 'line' => 3],
            ['url' => 'image.png', 'path' => 'image.png', 'line' => 4],
            ['url' => 'excluded.md', 'path' => 'excluded.md', 'line' => 5],
        ]);
        $source = new FileRecord('/project/docs/readme.md', 'docs/readme.md', new Entry(false, true, false, '/project/docs/readme.md'));
        $missing = new FileRecord('/project/docs/absent.md', 'docs/absent.md', new Entry(false, false, false, '/project/docs/absent.md'));
        $present = new FileRecord('/project/docs/image.png', 'docs/image.png', new Entry(false, true, false, '/project/docs/image.png'));
        $file = new StructuredFile($source, true, $links, null, ['absent.md' => $missing, 'image.png' => $present]);
        $plan = (new MarkdownLocalLinks())->evaluate(new InputSet(['documents' => new FileSet([$file], [], null)]), new Context('/project', '/project/guard.yaml', true));
        self::assertCount(1, $plan->findings);
        self::assertSame('documentation.missing_link_target', $plan->findings[0]->rule);
        self::assertStringContainsString('absent.md#intro', $plan->findings[0]->message);
        self::assertStringContainsString('docs/absent.md', $plan->findings[0]->message);
        self::assertSame([], $plan->changes);
    }
}
