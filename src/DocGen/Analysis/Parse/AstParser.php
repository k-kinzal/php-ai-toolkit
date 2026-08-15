<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\DocGenException;
use PhpParser\Error;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function sprintf;

/**
 * Parses PHP source into an AST with fully resolved names.
 */
final class AstParser
{
    /** @readonly */
    private PhpParserBridge $bridge;

    /**
     * Creates an AST parser from the version bridge.
     */
    public function __construct(?PhpParserBridge $bridge = null)
    {
        $this->bridge = $bridge ?? new PhpParserBridge();
    }

    /**
     * Parses one file's source code into resolved statements.
     *
     * @return list<Stmt>
     *
     * @throws DocGenException when the source cannot be parsed
     */
    public function parse(string $code, string $file): array
    {
        try {
            $statements = $this->bridge->parser()->parse($code);
        } catch (Error $error) {
            throw new DocGenException(sprintf('Failed to parse %s: %s', $file, $error->getMessage()), 0, $error);
        }

        if ($statements === null) {
            throw new DocGenException(sprintf('Failed to parse %s.', $file));
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(null, ['replaceNodes' => true]));
        $resolved = [];
        foreach ($traverser->traverse($statements) as $node) {
            if ($node instanceof Stmt) {
                $resolved[] = $node;
            }
        }

        return $resolved;
    }
}
