<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Architecture;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyCallResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyRestrictions;
use Toolkit\PhpStan\Rule\Architecture\Dependency\FileDependencyResolver;
use Toolkit\PhpStan\Rule\Architecture\Dependency\SymbolDependencyResolver;

/**
 * Forbids configured symbol and file-reading dependencies between project paths.
 *
 * @implements Rule<Node>
 */
final class ForbidDependencyRule implements Rule
{
    private DependencyPath $paths;

    private DependencyRestrictions $restrictions;

    private SymbolDependencyResolver $symbols;

    private FileDependencyResolver $files;

    /**
     * @param list<array{from: list<string>, excludeFrom?: list<string>, to: list<string>, excludeTo?: list<string>, allowSameRootDirectory?: bool}> $forbiddenDependencies
     * @param array<string, array{position: int, name: string}> $dependencyFileReaders
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        string $dependencyProjectRoot,
        array $forbiddenDependencies = DependencyRestrictions::DEFAULTS,
        array $dependencyFileReaders = [],
    ) {
        $this->paths = new DependencyPath($dependencyProjectRoot);
        $this->restrictions = new DependencyRestrictions($this->paths, $forbiddenDependencies);
        $calls = new DependencyCallResolver($reflectionProvider);
        $this->symbols = new SymbolDependencyResolver($reflectionProvider, $calls);
        $this->files = new FileDependencyResolver($calls, $this->paths, $dependencyFileReaders);
    }

    /**
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * Reports each resolved forbidden destination once per reference node.
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $source = $scope->isInTrait() ? $scope->getTraitReflection()->getFileName() ?? $scope->getFile() : $scope->getFile();
        if (!$this->restrictions->checks($source)) {
            return [];
        }

        $errors = [];
        $targets = $this->symbols->resolve($node, $scope) + $this->files->resolve($node, $scope);
        foreach ($targets as $target => $description) {
            $pattern = $this->restrictions->violation($source, $target);
            if ($pattern === null) {
                continue;
            }
            $errors[] = RuleErrorBuilder::message(sprintf(
                'Forbidden dependency from "%s" to "%s" via %s (target pattern "%s"). Remove the reference or move the referenced code/data to an allowed path.',
                $this->paths->display($source),
                $this->paths->display($target),
                $description,
                $pattern,
            ))->identifier('customRules.forbiddenDependency')->file($source)->line($node->getStartLine())->build();
        }

        return $errors;
    }
}
