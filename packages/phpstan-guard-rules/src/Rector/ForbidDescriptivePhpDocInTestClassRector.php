<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use InvalidArgumentException;
use Override;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher;
use Toolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector;

/**
 * Removes leading test PHPDoc prose while retaining complete annotation bodies.
 */
final class ForbidDescriptivePhpDocInTestClassRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var list<string>
     */
    private array $prefixes = ['Tests\\Unit', 'Tests\\Integration'];

    /**
     * Sets the same restricted namespace prefixes as the corresponding PHPStan rule.
     *
     * @param array<mixed> $configuration
     * @throws InvalidArgumentException
     */
    public function configure(array $configuration): void
    {
        $prefixes = [];
        foreach ($configuration as $prefix) {
            if (!is_string($prefix) || trim($prefix, '\\') === '') {
                throw new InvalidArgumentException('Pass non-empty namespace strings to ForbidDescriptivePhpDocInTestClassRector, for example Tests\\Unit.');
            }
            $prefixes[] = $prefix;
        }
        $this->prefixes = $prefixes;
    }

    /**
     * @return list<class-string<Node>>
     */
    #[Override]
    public function getNodeTypes(): array
    {
        return [ClassLike::class];
    }

    /**
     * Removes prose only from restricted test classes and their methods.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassLike) {
            return null;
        }

        if (!(new RestrictedTestNamespaceMatcher($this->prefixes))->matches($node)) {
            return null;
        }
        $changed = $this->removeProse($node);
        foreach ($node->getMethods() as $method) {
            $changed = $this->removeProse($method) || $changed;
        }

        return $changed ? $node : null;
    }

    /**
     * Keeps tag continuation lines, including multiline shapes and examples.
     */
    public function removeProse(Node $node): bool
    {
        $doc = $node->getDocComment();
        if ($doc === null || !(new DescriptivePhpDocTextDetector())->has($doc->getText())) {
            return false;
        }
        $body = substr($doc->getText(), 3, -2);
        if (preg_match('/(?:^|\R)[\t *]*(@[a-zA-Z][\s\S]*)/', $body, $match) === 1) {
            $node->setDocComment(new Doc("/**\n * " . rtrim($match[1]) . "\n */"));
        } else {
            $node->setAttribute('comments', array_values(array_filter($node->getComments(), static fn ($comment): bool => $comment !== $doc)));
        }

        return true;
    }
}
