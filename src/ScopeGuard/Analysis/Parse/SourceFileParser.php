<?php

declare(strict_types=1);

namespace PhpAiToolkit\ScopeGuard\Analysis\Parse;

use function file_get_contents;
use function is_file;

use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;

use function sprintf;

/**
 * Parses one source file into statements whose names are fully qualified.
 *
 * Name resolution is what makes a standalone checker possible: the traverser
 * applies the file's namespace and its import table to every written name, so a
 * reference can be matched against a declaration without inferring any types.
 *
 * @visibility parent
 */
final class SourceFileParser
{
    /** @readonly */
    private PhpParserBridge $bridge;

    /**
     * Creates a parser from the php-parser version bridge.
     */
    public function __construct(?PhpParserBridge $bridge = null)
    {
        $this->bridge = $bridge ?? new PhpParserBridge();
    }

    /**
     * Returns the name-resolved statements of one PHP file.
     *
     * @return list<\PhpParser\Node\Stmt>
     *
     * @throws ScopeGuardException when the file cannot be read or parsed
     */
    public function parse(string $path): array
    {
        $source = is_file($path) ? file_get_contents($path) : false;
        if ($source === false) {
            throw new ScopeGuardException(sprintf('Could not read source file: %s', $path));
        }

        $errorHandler = new Collecting();
        $statements = $this->bridge->parser()->parse($source, $errorHandler);
        foreach ($errorHandler->getErrors() as $error) {
            throw new ScopeGuardException(sprintf('Could not parse %s: %s', $path, $error->getMessage()), 0, $error);
        }

        if ($statements === null) {
            throw new ScopeGuardException(sprintf('Could not parse %s: the parser produced no statements.', $path));
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        /** @var list<\PhpParser\Node\Stmt> $resolved */
        $resolved = $traverser->traverse($statements);

        return $resolved;
    }
}
