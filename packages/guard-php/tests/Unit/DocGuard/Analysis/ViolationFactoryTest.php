<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Analysis\ViolationFactory;
use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * @covers \Toolkit\DocGuard\Analysis\ViolationFactory
 * @uses \Toolkit\DocGuard\Config\DeclaredHeading
 * @uses \Toolkit\DocGuard\Markdown\Heading
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(ViolationFactory::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(Heading::class)]
#[UsesClass(Violation::class)]
final class ViolationFactoryTest extends TestCase
{
    public function testUnexpectedHeadingNamesTheAddedHeading(): void
    {
        $violation = (new ViolationFactory())->unexpectedHeading('README.md', new Heading(2, 'Development', 40), 'doc-guard.yaml');

        self::assertSame('unexpected_heading', $violation->rule);
        self::assertSame(40, $violation->line);
        self::assertNull($violation->expected);
        self::assertSame('## Development', $violation->actual);
        self::assertSame('Heading "## Development" is not declared for README.md in doc-guard.yaml. Remove the heading and write its content inside an existing section; adding a section requires a human to update doc-guard.yaml.', $violation->message);
    }

    public function testMissingHeadingNamesTheDeclaredPosition(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')];

        $violation = (new ViolationFactory())->missingHeading('README.md', $declared, 1, 'doc-guard.yaml');

        self::assertSame('missing_heading', $violation->rule);
        self::assertNull($violation->line);
        self::assertSame('## Usage', $violation->expected);
        self::assertStringContainsString('Declared heading "## Usage" is missing from README.md; doc-guard.yaml declares it after "# Tool".', $violation->message);
    }

    public function testRenamedHeadingNamesBothHeadings(): void
    {
        $violation = (new ViolationFactory())->renamedHeading('README.md', new DeclaredHeading(2, 'Usage'), new Heading(2, 'Getting Started', 8), 'doc-guard.yaml');

        self::assertSame('renamed_heading', $violation->rule);
        self::assertSame('## Usage', $violation->expected);
        self::assertSame('## Getting Started', $violation->actual);
        self::assertStringContainsString('Heading "## Getting Started" replaces the declared heading "## Usage" in README.md.', $violation->message);
    }

    public function testChangedHeadingLevelNamesBothLevels(): void
    {
        $violation = (new ViolationFactory())->changedHeadingLevel('README.md', new DeclaredHeading(2, 'Usage'), new Heading(3, 'Usage', 8), 'doc-guard.yaml');

        self::assertSame('changed_heading_level', $violation->rule);
        self::assertStringContainsString('Heading "### Usage" is level 3, but doc-guard.yaml declares "## Usage" at level 2 in README.md.', $violation->message);
    }

    public function testMovedHeadingNamesTheDeclaredPosition(): void
    {
        $declared = [new DeclaredHeading(2, 'Requirements'), new DeclaredHeading(2, 'Usage')];

        $violation = (new ViolationFactory())->movedHeading('README.md', $declared, 0, new Heading(2, 'Requirements', 20), 'doc-guard.yaml');

        self::assertSame('moved_heading', $violation->rule);
        self::assertSame(20, $violation->line);
        self::assertStringContainsString('it is declared as the first heading.', $violation->message);
    }

    public function testMissingDocumentNamesThePath(): void
    {
        $violation = (new ViolationFactory())->missingDocument('docs/guide.md', 'doc-guard.yaml');

        self::assertSame('missing_document', $violation->rule);
        self::assertSame('Declared document docs/guide.md does not exist. Restore the document; removing or renaming a document requires a human to update doc-guard.yaml.', $violation->message);
    }

    public function testUndeclaredDocumentNamesThePattern(): void
    {
        $violation = (new ViolationFactory())->undeclaredDocument('docs/development.md', 'docs/**/*.md', 'doc-guard.yaml');

        self::assertSame('undeclared_document', $violation->rule);
        self::assertStringContainsString('Document docs/development.md matches the scan pattern "docs/**/*.md" but is not declared in doc-guard.yaml.', $violation->message);
    }

    public function testPositionDescribesThePreviousDeclaredHeading(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')];

        self::assertSame('as the first heading', (new ViolationFactory())->position($declared, 0));
        self::assertSame('after "# Tool"', (new ViolationFactory())->position($declared, 1));
    }
}
