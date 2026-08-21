<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function array_merge;
use function array_values;
use function basename;
use function file_get_contents;
use function is_file;

use PhpAiToolkit\Doctest\DoctestException;
use PhpParser\ErrorHandler\Collecting;

use function preg_match;
use function sprintf;

/**
 * Reads the documented declarations of one PHP source file.
 *
 * Only declarations that carry a docblock become targets, because a docblock is
 * the only place an example can be written. The scan is a plain recursive walk
 * over the parsed statements rather than a node visitor, since the visitor
 * interface differs between the supported nikic/php-parser majors while the
 * node accessors do not.
 */
final class SourceScanner
{
    /** @readonly */
    private PhpParserBridge $bridge;

    /** @readonly */
    private ImportPreamble $preamble;

    private ?string $displayPath = null;

    /**
     * Creates a scanner from the php-parser version bridge and import rendering.
     */
    public function __construct(?PhpParserBridge $bridge = null, ?ImportPreamble $preamble = null)
    {
        $this->bridge = $bridge ?? new PhpParserBridge();
        $this->preamble = $preamble ?? new ImportPreamble();
    }

    /**
     * Returns every documented declaration of the file, in source order.
     *
     * @return list<Target>
     *
     * @param string $path the readable path of the file
     * @param string|null $displayPath the path reports name the file by
     *
     * @throws DoctestException when the file cannot be read or parsed
     */
    public function scan(string $path, ?string $displayPath = null): array
    {
        $source = is_file($path) ? file_get_contents($path) : false;
        if ($source === false) {
            throw new DoctestException(sprintf('Could not read source file: %s', $path));
        }

        $statements = $this->statements($path, $source);
        $this->displayPath = $displayPath;
        $declarations = $this->declarations($statements, $path, '', null, $this->preamble->render($statements));
        $fileTarget = $this->fileTarget($path, $source, $declarations);

        return $fileTarget === null ? $declarations : array_merge([$fileTarget], $declarations);
    }

    /**
     * Parses the source into statements.
     *
     * @return list<\PhpParser\Node\Stmt>
     *
     * @throws DoctestException when the source cannot be parsed
     */
    public function statements(string $path, string $source): array
    {
        $errorHandler = new Collecting();
        $statements = $this->bridge->parser()->parse($source, $errorHandler);
        foreach ($errorHandler->getErrors() as $error) {
            throw new DoctestException(sprintf('Could not parse %s: %s', $path, $error->getMessage()), 0, $error);
        }

        if ($statements === null) {
            throw new DoctestException(sprintf('Could not parse %s: the parser produced no statements.', $path));
        }

        return array_values($statements);
    }

    /**
     * Returns the file-level docblock target, or null when the file has none.
     *
     * A docblock that the parser already attached to the first declaration
     * documents that declaration, not the file, so it is not reported twice.
     *
     * @param list<Target> $declarations
     */
    public function fileTarget(string $path, string $source, array $declarations): ?Target
    {
        $matches = [];
        if (preg_match('/^<\?php\s*(?:declare\s*\([^)]*\)\s*;\s*)?(\/\*\*.*?\*\/)/s', $source, $matches) !== 1) {
            return null;
        }

        foreach ($declarations as $declaration) {
            if ($declaration->docComment === $matches[1]) {
                return null;
            }
        }

        return new Target(Target::FILE, $path, $matches[1], basename($path), 1, '', null, [], $this->displayPath);
    }

    /**
     * Returns the documented declarations of the given statements.
     *
     * @param array<mixed> $statements
     * @param list<string> $imports
     * @return list<Target>
     */
    public function declarations(array $statements, string $path, string $namespace, ?string $className, array $imports): array
    {
        $targets = [];
        foreach ($statements as $statement) {
            if ($statement instanceof \PhpParser\Node\Stmt\Namespace_) {
                $inner = $statement->name === null ? '' : $statement->name->toString();
                $targets = array_merge($targets, $this->declarations($statement->stmts, $path, $inner, null, $this->preamble->render($statement->stmts)));
                continue;
            }

            if ($statement instanceof \PhpParser\Node\Stmt\ClassLike) {
                $targets = array_merge($targets, $this->classLikeTargets($statement, $path, $namespace, $imports));
                continue;
            }

            if ($statement instanceof \PhpParser\Node\Stmt\Function_) {
                $target = $this->target(Target::FUNCTION, $statement, $path, $statement->name->toString(), $namespace, null, $imports);
                $targets = array_merge($targets, $target === null ? [] : [$target]);
                continue;
            }

            if ($statement instanceof \PhpParser\Node\Stmt\ClassMethod && $className !== null) {
                $target = $this->target(Target::METHOD, $statement, $path, $statement->name->toString(), $namespace, $className, $imports);
                $targets = array_merge($targets, $target === null ? [] : [$target]);
                continue;
            }

            if ($statement instanceof \PhpParser\Node) {
                $targets = array_merge($targets, $this->nestedDeclarations($statement, $path, $namespace, $className, $imports));
            }
        }

        return $targets;
    }

    /**
     * Returns the documented declarations of one class-like and its methods.
     *
     * @param list<string> $imports
     * @return list<Target>
     */
    public function classLikeTargets(\PhpParser\Node\Stmt\ClassLike $node, string $path, string $namespace, array $imports): array
    {
        if ($node->name === null) {
            return [];
        }

        $name = $node->name->toString();
        $target = $this->target(Target::CLASS_LIKE, $node, $path, $name, $namespace, null, $imports);
        $targets = $target === null ? [] : [$target];

        return array_merge($targets, $this->declarations($node->stmts, $path, $namespace, $name, $imports));
    }

    /**
     * Returns the documented declarations nested inside a control-flow statement.
     *
     * @param list<string> $imports
     * @return list<Target>
     */
    public function nestedDeclarations(\PhpParser\Node $statement, string $path, string $namespace, ?string $className, array $imports): array
    {
        $values = get_object_vars($statement);
        $nested = [];
        foreach ($statement->getSubNodeNames() as $subNodeName) {
            $value = $values[$subNodeName] ?? null;
            if (is_array($value)) {
                $nested = array_merge($nested, $this->declarations($value, $path, $namespace, $className, $imports));
            }
        }

        return $nested;
    }

    /**
     * Builds a target for a documented node, or returns null when it has no docblock.
     *
     * @param list<string> $imports
     */
    public function target(string $kind, \PhpParser\Node $node, string $path, string $name, string $namespace, ?string $className, array $imports): ?Target
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return null;
        }

        return new Target($kind, $path, $docComment->getText(), $name, $docComment->getStartLine(), $namespace, $className, $imports, $this->displayPath);
    }
}
