<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowsDeclarationInspector;
use Toolkit\PhpStan\Rule\ExceptionHandling\ThrowSiteCollector;

/**
 * Documents escaping direct throws; the resulting API contract needs review.
 */
final class RequireThrowsTagOnDirectThrowRector extends AbstractRector
{
    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * Appends specific missing tags using the same catch analysis as PHPStan.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if ($node->stmts === null || $node->stmts === []) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        $class = $scope->getClassReflection();
        if ($class === null) {
            return null;
        }

        $declared = $class->getMethod($node->name->toString(), $scope)->getThrowType();
        $inspector = new ThrowsDeclarationInspector();
        $missing = [];
        foreach ((new ThrowSiteCollector())->collect($node->stmts) as $site) {
            foreach ($inspector->uncoveredClassNames($site, $scope, $declared) as $name) {
                if (!in_array(strtolower($name), ['exception', 'throwable'], true)) {
                    $missing[$name] = true;
                }
            }
        }
        if ($missing === []) {
            return null;
        }

        $text = $node->getDocComment()?->getText() ?? "/**\n */";
        $text = rtrim(substr($text, 0, -2));
        foreach (array_keys($missing) as $name) {
            $tag = '@throws \\' . $name;
            if (preg_match('/' . preg_quote($tag, '/') . '(?=\s|\*\/|$)/i', $text) !== 1) {
                $text .= "\n * " . $tag;
            }
        }
        $node->setDocComment(new Doc($text . "\n */"));

        return $node;
    }
}
