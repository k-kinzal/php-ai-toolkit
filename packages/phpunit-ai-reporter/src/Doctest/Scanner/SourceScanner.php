<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Scanner;

use function basename;
use function file_get_contents;

use Generator;

use function get_object_vars;
use function is_array;
use function is_file;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Parser;

use function preg_match;

use RuntimeException;

/**
 * Scans PHP source files to extract targets (classes, methods, functions) with docblocks.
 *
 * Uses PHP-Parser to analyze the AST (Abstract Syntax Tree) of source files
 * and extract all elements that have documentation blocks.
 *
 * The walk is written out here rather than delegated to a NodeVisitor, which is
 * how k-kinzal/doctest-php does it: the visitor interface signature differs
 * between the nikic/php-parser majors this package supports, while the node
 * accessors it uses do not.
 */
final class SourceScanner
{
    /** @readonly */
    private Parser $parser;

    /**
     * Creates a scanner backed by a parser for the newest supported PHP version.
     *
     * @throws RuntimeException when no parser can be created
     */
    public function __construct(?ParserFactoryBridge $bridge = null)
    {
        $this->parser = ($bridge ?? new ParserFactoryBridge())->create();
    }

    /**
     * Scans a PHP file and yields all targets with docblocks.
     *
     * Analyzes the file's AST to find file-level docblocks, classes, methods,
     * and functions that have documentation. A file that cannot be read or
     * parsed yields nothing.
     *
     * @param string $filePath absolute path to the PHP file
     * @return Generator<int, Target> targets found in the file
     */
    public function scanFile(string $filePath): Generator
    {
        $code = is_file($filePath) ? file_get_contents($filePath) : false;
        if ($code === false) {
            return;
        }

        $errorHandler = new Collecting();
        $ast = $this->parser->parse($code, $errorHandler);
        if ($ast === null || $errorHandler->hasErrors()) {
            return;
        }

        $fileDocblock = $this->extractFileDocblock($code);
        if ($fileDocblock !== null) {
            yield new Target(TargetKind::FILE, $filePath, $fileDocblock, basename($filePath), 1);
        }

        yield from $this->traverseAst($ast, $filePath, null, null);
    }

    /**
     * Returns the file-level docblock of the source, or null when it has none.
     *
     * Matches the first docblock that appears after the opening tag and before
     * any other code. An optional declare statement is skipped, which
     * k-kinzal/doctest-php does not do: PHP-CS-Fixer puts declare(strict_types=1)
     * directly after the opening tag, so a file-level docblock in a formatted
     * project always sits behind it.
     */
    public function extractFileDocblock(string $code): ?string
    {
        $matches = [];
        if (preg_match('/^<\?php\s*(?:declare\s*\([^)]*\)\s*;\s*)?(\/\*\*.*?\*\/)/s', $code, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Yields every documented class, method, and function of the given nodes.
     *
     * @param array<mixed> $nodes
     * @return Generator<int, Target>
     */
    public function traverseAst(array $nodes, string $filePath, ?string $namespace, ?string $className): Generator
    {
        foreach ($nodes as $node) {
            if (!$node instanceof \PhpParser\Node) {
                continue;
            }

            yield from $this->targetsOf($node, $filePath, $namespace, $className);
        }
    }

    /**
     * Yields the targets one node contributes, then descends into it.
     *
     * @return Generator<int, Target>
     */
    public function targetsOf(\PhpParser\Node $node, string $filePath, ?string $namespace, ?string $className): Generator
    {
        if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
            yield from $this->traverseAst($node->stmts, $filePath, $node->name === null ? null : $node->name->toString(), null);

            return;
        }

        if ($node instanceof \PhpParser\Node\Stmt\Class_) {
            yield from $this->classTargets($node, $filePath, $namespace);

            return;
        }

        if ($node instanceof \PhpParser\Node\Stmt\ClassMethod) {
            yield from $this->methodTarget($node, $filePath, $namespace, $className);

            return;
        }

        if ($node instanceof \PhpParser\Node\Stmt\Function_) {
            yield from $this->functionTarget($node, $filePath, $namespace);

            return;
        }

        yield from $this->traverseAst($this->children($node), $filePath, $namespace, $className);
    }

    /**
     * Yields the target of a documented class and then of its members.
     *
     * @return Generator<int, Target>
     */
    public function classTargets(\PhpParser\Node\Stmt\Class_ $node, string $filePath, ?string $namespace): Generator
    {
        $className = $node->name === null ? null : $node->name->toString();
        $docComment = $node->getDocComment();
        if ($docComment !== null && $className !== null) {
            yield new Target(TargetKind::CLASS_LIKE, $filePath, $docComment->getText(), $className, $docComment->getStartLine(), $namespace);
        }

        yield from $this->traverseAst($node->stmts, $filePath, $namespace, $className);
    }

    /**
     * Yields the target of a documented method declared inside a class.
     *
     * @return Generator<int, Target>
     */
    public function methodTarget(\PhpParser\Node\Stmt\ClassMethod $node, string $filePath, ?string $namespace, ?string $className): Generator
    {
        $docComment = $node->getDocComment();
        if ($docComment === null || $className === null) {
            return;
        }

        yield new Target(
            TargetKind::METHOD,
            $filePath,
            $docComment->getText(),
            $node->name->toString(),
            $docComment->getStartLine(),
            $namespace,
            $className,
            $node->isStatic(),
        );
    }

    /**
     * Yields the target of a documented function.
     *
     * @return Generator<int, Target>
     */
    public function functionTarget(\PhpParser\Node\Stmt\Function_ $node, string $filePath, ?string $namespace): Generator
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return;
        }

        yield new Target(TargetKind::FUNCTION, $filePath, $docComment->getText(), $node->name->toString(), $docComment->getStartLine(), $namespace);
    }

    /**
     * Returns the direct sub-node values of one node.
     *
     * @return list<\PhpParser\Node>
     */
    public function children(\PhpParser\Node $node): array
    {
        $values = get_object_vars($node);
        $children = [];
        foreach ($node->getSubNodeNames() as $name) {
            $value = $values[$name] ?? null;
            if (is_array($value) || $value instanceof \PhpParser\Node) {
                $children[] = $value;
            }
        }

        $flattened = [];
        foreach ($children as $child) {
            if (is_array($child)) {
                foreach ($child as $item) {
                    if ($item instanceof \PhpParser\Node) {
                        $flattened[] = $item;
                    }
                }
                continue;
            }

            $flattened[] = $child;
        }

        return $flattened;
    }
}
