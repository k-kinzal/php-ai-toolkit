<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;

use function strtolower;

/**
 * Collects class import aliases from use statements.
 *
 * The map is keyed by the lowercased alias so PHPDoc type names can be
 * resolved case-insensitively when rendering.
 */
final class UseMapCollector
{
    /**
     * Collects the class use aliases declared in a statement list.
     *
     * @param array<Stmt> $statements
     *
     * @return array<string, string>
     */
    public function collect(array $statements): array
    {
        $map = [];
        foreach ($statements as $statement) {
            if ($statement instanceof Use_ && $statement->type === Use_::TYPE_NORMAL) {
                foreach ($statement->uses as $use) {
                    $map[strtolower($use->getAlias()->toString())] = $use->name->toString();
                }
            }

            if ($statement instanceof GroupUse) {
                foreach ($statement->uses as $use) {
                    $effectiveType = $use->type !== Use_::TYPE_UNKNOWN ? $use->type : $statement->type;
                    if ($effectiveType === Use_::TYPE_NORMAL) {
                        $map[strtolower($use->getAlias()->toString())] = $statement->prefix->toString() . '\\' . $use->name->toString();
                    }
                }
            }
        }

        return $map;
    }
}
