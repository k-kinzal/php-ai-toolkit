<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\DocGenException;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function sprintf;

/**
 * Parses PHP source into an AST with fully resolved names.
 *
 * Syntax errors are collected rather than thrown: the two supported
 * php-parser majors disagree on whether the throwing handler is documented,
 * and collecting them keeps the failure a plain return value that is turned
 * into a DocGenException here.
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
        $errorHandler = new Collecting();
        $statements = $this->bridge->parser()->parse($code, $errorHandler);
        $errors = $errorHandler->getErrors();
        if (isset($errors[0])) {
            throw new DocGenException(sprintf('Failed to parse %s: %s', $file, $errors[0]->getMessage()), 0, $errors[0]);
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
