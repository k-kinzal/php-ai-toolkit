<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Config\Value\DeclaredHeading;
use Guard\Reporting\HeadingViolation;
use Guard\Reporting\HeadingViolationFactory;
use Guard\Structure\Markdown\Heading;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\HeadingViolationFactory
 * @uses \Guard\Config\Value\DeclaredHeading
 * @uses \Guard\Reporting\HeadingViolation
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(HeadingViolationFactory::class)]
#[UsesClass(DeclaredHeading::class)]
#[UsesClass(HeadingViolation::class)]
#[UsesClass(Heading::class)]
final class HeadingViolationFactoryTest extends TestCase
{
    public function testUnexpectedHeadingNamesTheAddedHeading(): void
    {
        $violation = (new HeadingViolationFactory())->unexpectedHeading('README.md', new Heading(2, 'Development', 40), 'doc-guard.yaml');

        self::assertSame('unexpected_heading', $violation->rule);
        self::assertSame(40, $violation->line);
        self::assertNull($violation->expected);
        self::assertSame('## Development', $violation->actual);
        self::assertSame('Heading "## Development" is not declared for README.md in doc-guard.yaml. Remove the heading and write its content inside an existing section; adding a section requires a human to update doc-guard.yaml.', $violation->message);
    }

    public function testMissingHeadingNamesTheDeclaredPosition(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')];

        $violation = (new HeadingViolationFactory())->missingHeading('README.md', $declared, 1, 'doc-guard.yaml');

        self::assertSame('missing_heading', $violation->rule);
        self::assertNull($violation->line);
        self::assertSame('## Usage', $violation->expected);
        self::assertStringContainsString('Declared heading "## Usage" is missing from README.md; doc-guard.yaml declares it after "# Tool".', $violation->message);
    }

    public function testRenamedHeadingNamesBothHeadings(): void
    {
        $violation = (new HeadingViolationFactory())->renamedHeading('README.md', new DeclaredHeading(2, 'Usage'), new Heading(2, 'Getting Started', 8), 'doc-guard.yaml');

        self::assertSame('renamed_heading', $violation->rule);
        self::assertSame('## Usage', $violation->expected);
        self::assertSame('## Getting Started', $violation->actual);
        self::assertStringContainsString('Heading "## Getting Started" replaces the declared heading "## Usage" in README.md.', $violation->message);
    }

    public function testChangedHeadingLevelNamesBothLevels(): void
    {
        $violation = (new HeadingViolationFactory())->changedHeadingLevel('README.md', new DeclaredHeading(2, 'Usage'), new Heading(3, 'Usage', 8), 'doc-guard.yaml');

        self::assertSame('changed_heading_level', $violation->rule);
        self::assertStringContainsString('Heading "### Usage" is level 3, but doc-guard.yaml declares "## Usage" at level 2 in README.md.', $violation->message);
    }

    public function testMovedHeadingNamesTheDeclaredPosition(): void
    {
        $declared = [new DeclaredHeading(2, 'Requirements'), new DeclaredHeading(2, 'Usage')];

        $violation = (new HeadingViolationFactory())->movedHeading('README.md', $declared, 0, new Heading(2, 'Requirements', 20), 'doc-guard.yaml');

        self::assertSame('moved_heading', $violation->rule);
        self::assertSame(20, $violation->line);
        self::assertStringContainsString('it is declared as the first heading.', $violation->message);
    }

    public function testMissingDocumentNamesThePath(): void
    {
        $violation = (new HeadingViolationFactory())->missingDocument('docs/guide.md', 'doc-guard.yaml');

        self::assertSame('missing_document', $violation->rule);
        self::assertSame('Declared document docs/guide.md does not exist. Restore the document; removing or renaming a document requires a human to update doc-guard.yaml.', $violation->message);
    }

    public function testUndeclaredDocumentNamesThePattern(): void
    {
        $violation = (new HeadingViolationFactory())->undeclaredDocument('docs/development.md', 'docs/**/*.md', 'doc-guard.yaml');
        self::assertSame('undeclared_document', $violation->rule);
        self::assertStringContainsString('Document docs/development.md matches the scan pattern "docs/**/*.md" but is not declared in doc-guard.yaml.', $violation->message);
    }

    public function testPositionDescribesThePreviousDeclaredHeading(): void
    {
        $declared = [new DeclaredHeading(1, 'Tool'), new DeclaredHeading(2, 'Usage')];

        self::assertSame('as the first heading', (new HeadingViolationFactory())->position($declared, 0));
        self::assertSame('after "# Tool"', (new HeadingViolationFactory())->position($declared, 1));
    }
}
