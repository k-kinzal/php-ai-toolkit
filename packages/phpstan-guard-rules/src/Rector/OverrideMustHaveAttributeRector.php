<?php

declare(strict_types=1);

namespace Toolkit\Rector;

use Override;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\TrinaryLogic;
use Rector\PHPStan\ScopeFetcher;
use Rector\Rector\AbstractRector;
use Toolkit\PhpStan\Rule\ClassDesign\OverridableMethodPolicy;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;

/**
 * Adds Override only where the PHPStan rule proves a concrete parent override.
 */
final class OverrideMustHaveAttributeRector extends AbstractRector
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
     * Adds the attribute, excluding constructors, private and abstract parents.
     */
    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassMethod) {
            return null;
        }

        if ((new OverrideAttributeDetector())->has($node)) {
            return null;
        }
        $scope = ScopeFetcher::fetch($node);
        $parent = $scope->getClassReflection()?->getParentClass();
        $name = $node->name->toString();
        if ($parent === null || !$parent->hasMethod($name)) {
            return null;
        }
        $method = $parent->getMethod($name, $scope);
        $abstract = $method->isAbstract();
        if ($abstract instanceof TrinaryLogic ? $abstract->yes() : $abstract === true) {
            return null;
        }
        if (!(new OverridableMethodPolicy())->allows($name, $method)) {
            return null;
        }

        $node->attrGroups[] = new AttributeGroup([new Attribute(new FullyQualified('Override'))]);

        return $node;
    }
}
