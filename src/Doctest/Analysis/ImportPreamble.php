<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function implode;
use function sprintf;

/**
 * Renders the import statements of a file back into source text.
 *
 * An example is evaluated on its own, and evaluated code inherits no import
 * table, so the imports of the documenting file are replayed in front of it.
 * That lets an example spell a class the way the surrounding file spells it
 * instead of fully qualifying every name.
 */
final class ImportPreamble
{
    /**
     * Returns one import statement per import found in the given statements.
     *
     * @param array<mixed> $statements
     * @return list<string>
     */
    public function render(array $statements): array
    {
        $imports = [];
        foreach ($statements as $statement) {
            if ($statement instanceof \PhpParser\Node\Stmt\Use_) {
                $imports[] = $this->useStatement($statement);
                continue;
            }

            if ($statement instanceof \PhpParser\Node\Stmt\GroupUse) {
                $imports[] = $this->groupUseStatement($statement);
            }
        }

        return $imports;
    }

    /**
     * Renders a plain import statement.
     */
    public function useStatement(\PhpParser\Node\Stmt\Use_ $node): string
    {
        $names = [];
        foreach ($node->uses as $use) {
            $names[] = $this->itemName($use->name->toString(), $use->alias === null ? null : $use->alias->toString());
        }

        return sprintf('use %s%s;', $this->keyword($node->type), implode(', ', $names));
    }

    /**
     * Renders a grouped import statement as one flat import per name.
     */
    public function groupUseStatement(\PhpParser\Node\Stmt\GroupUse $node): string
    {
        $prefix = $node->prefix->toString();
        $statements = [];
        foreach ($node->uses as $use) {
            $keyword = $this->keyword($node->type === \PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN ? $use->type : $node->type);
            $statements[] = sprintf(
                'use %s%s;',
                $keyword,
                $this->itemName($prefix . '\\' . $use->name->toString(), $use->alias === null ? null : $use->alias->toString()),
            );
        }

        return implode("\n", $statements);
    }

    /**
     * Renders one imported name with its alias when it has one.
     */
    public function itemName(string $name, ?string $alias): string
    {
        return $alias === null ? $name : sprintf('%s as %s', $name, $alias);
    }

    /**
     * Returns the keyword that follows "use" for the given import type.
     */
    public function keyword(int $type): string
    {
        if ($type === \PhpParser\Node\Stmt\Use_::TYPE_FUNCTION) {
            return 'function ';
        }

        return $type === \PhpParser\Node\Stmt\Use_::TYPE_CONSTANT ? 'const ' : '';
    }
}
