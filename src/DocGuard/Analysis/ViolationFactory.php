<?php

declare(strict_types=1);

namespace Toolkit\DocGuard\Analysis;

use function sprintf;

use Toolkit\DocGuard\Config\DeclaredHeading;
use Toolkit\DocGuard\Markdown\Heading;

/**
 * Builds DocGuard violations with messages that name the change and its fix.
 */
final class ViolationFactory
{
    /**
     * Reports a heading that the declared structure does not contain.
     */
    public function unexpectedHeading(string $path, Heading $actual, string $configName): Violation
    {
        return new Violation($path, $actual->line, 'unexpected_heading', null, $actual->notation(), sprintf(
            'Heading "%s" is not declared for %s in %s. Remove the heading and write its content inside an existing section; adding a section requires a human to update %s.',
            $actual->notation(),
            $path,
            $configName,
            $configName,
        ));
    }

    /**
     * Reports a declared heading that the document no longer contains.
     *
     * @param list<DeclaredHeading> $declared
     */
    public function missingHeading(string $path, array $declared, int $index, string $configName): Violation
    {
        $expected = $declared[$index]->notation();

        return new Violation($path, null, 'missing_heading', $expected, null, sprintf(
            'Declared heading "%s" is missing from %s; %s declares it %s. Restore the heading at that position; removing a section requires a human to update %s.',
            $expected,
            $path,
            $configName,
            $this->position($declared, $index),
            $configName,
        ));
    }

    /**
     * Reports a heading found where a different declared heading is expected.
     */
    public function renamedHeading(string $path, DeclaredHeading $expected, Heading $actual, string $configName): Violation
    {
        return new Violation($path, $actual->line, 'renamed_heading', $expected->notation(), $actual->notation(), sprintf(
            'Heading "%s" replaces the declared heading "%s" in %s. Restore the declared heading and edit only the section content; renaming a section requires a human to update %s.',
            $actual->notation(),
            $expected->notation(),
            $path,
            $configName,
        ));
    }

    /**
     * Reports a declared heading whose level changed.
     */
    public function changedHeadingLevel(string $path, DeclaredHeading $expected, Heading $actual, string $configName): Violation
    {
        return new Violation($path, $actual->line, 'changed_heading_level', $expected->notation(), $actual->notation(), sprintf(
            'Heading "%s" is level %d, but %s declares "%s" at level %d in %s. Restore the declared level; changing the nesting of sections requires a human to update %s.',
            $actual->notation(),
            $actual->level,
            $configName,
            $expected->notation(),
            $expected->level,
            $path,
            $configName,
        ));
    }

    /**
     * Reports a declared heading that appears out of its declared order.
     *
     * @param list<DeclaredHeading> $declared
     */
    public function movedHeading(string $path, array $declared, int $index, Heading $actual, string $configName): Violation
    {
        return new Violation($path, $actual->line, 'moved_heading', $declared[$index]->notation(), $actual->notation(), sprintf(
            'Heading "%s" is out of the order declared in %s for %s; it is declared %s. Move the section back to that position; reordering sections requires a human to update %s.',
            $actual->notation(),
            $configName,
            $path,
            $this->position($declared, $index),
            $configName,
        ));
    }

    /**
     * Reports a declared document that does not exist.
     */
    public function missingDocument(string $path, string $configName): Violation
    {
        return new Violation($path, null, 'missing_document', null, null, sprintf(
            'Declared document %s does not exist. Restore the document; removing or renaming a document requires a human to update %s.',
            $path,
            $configName,
        ));
    }

    /**
     * Reports a document matched by a scan pattern but not declared.
     */
    public function undeclaredDocument(string $path, string $pattern, string $configName): Violation
    {
        return new Violation($path, null, 'undeclared_document', null, null, sprintf(
            'Document %s matches the scan pattern "%s" but is not declared in %s. Move its content into an existing section of a declared document and delete the file; adding a document requires a human to update %s.',
            $path,
            $pattern,
            $configName,
            $configName,
        ));
    }

    /**
     * Describes the declared position of a heading by the heading declared before it.
     *
     * @param list<DeclaredHeading> $declared
     */
    public function position(array $declared, int $index): string
    {
        $previous = $declared[$index - 1] ?? null;
        if ($previous === null) {
            return 'as the first heading';
        }

        return sprintf('after "%s"', $previous->notation());
    }
}
