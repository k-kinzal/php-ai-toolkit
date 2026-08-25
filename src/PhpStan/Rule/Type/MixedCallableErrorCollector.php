<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type;

use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Rules\IdentifierRuleError;

use function sprintf;
use function str_contains;
use function strrpos;
use function strtolower;
use function substr;

use Toolkit\PhpStan\Rule\ClassDesign\MagicMethodRegistry;

/**
 * Collects mixed diagnostics from callable signatures.
 *
 * @visibility namespace
 */
final class MixedCallableErrorCollector
{
    /** @readonly */
    private ConcreteMixedTypeInspector $typeInspector;

    /** @readonly */
    private MixedVisibilityDetector $visibilityDetector;

    /** @readonly */
    private InheritedMixedContractInspector $contractInspector;

    /** @readonly */
    private MixedTypeErrorBuilder $errorBuilder;

    /** @readonly */
    private MagicMethodRegistry $magicMethods;

    /**
     * Creates the collector from type, visibility, and contract policies.
     */
    public function __construct(
        ?ConcreteMixedTypeInspector $typeInspector = null,
        ?MixedVisibilityDetector $visibilityDetector = null,
        ?InheritedMixedContractInspector $contractInspector = null,
        ?MixedTypeErrorBuilder $errorBuilder = null,
        ?MagicMethodRegistry $magicMethods = null,
    ) {
        $this->typeInspector = $typeInspector ?? new ConcreteMixedTypeInspector();
        $this->visibilityDetector = $visibilityDetector ?? new MixedVisibilityDetector();
        $this->contractInspector = $contractInspector ?? new InheritedMixedContractInspector($this->typeInspector);
        $this->errorBuilder = $errorBuilder ?? new MixedTypeErrorBuilder();
        $this->magicMethods = $magicMethods ?? new MagicMethodRegistry();
    }

    /**
     * Collects errors from one class method.
     *
     * @return list<IdentifierRuleError>
     */
    public function classMethod(InClassMethodNode $node): array
    {
        $original = $node->getOriginalNode();
        $class = $node->getClassReflection();
        $methodName = $original->name->toString();
        if ($this->isMagicProtocol($methodName)) {
            return [];
        }

        $docComment = $original->getDocComment();
        $restricted = $original->isPrivate()
            || $this->visibilityDetector->classIsRestricted($class)
            || $this->visibilityDetector->isRestricted(
                $docComment === null ? null : $docComment->getText(),
                $class->getNativeReflection()->getNamespaceName()
            );
        if (!$restricted) {
            return [];
        }

        $variants = $node->getMethodReflection()->getVariants();
        if (!isset($variants[0])) {
            return [];
        }

        return $this->signatureErrors($variants[0], $original, sprintf('%s::%s()', $class->getDisplayName(), $methodName), $class, $methodName);
    }

    /**
     * Collects errors from one named function.
     *
     * @return list<IdentifierRuleError>
     */
    public function function(InFunctionNode $node): array
    {
        $original = $node->getOriginalNode();
        $function = $node->getFunctionReflection();
        $name = $function->getName();
        $separator = strrpos($name, '\\');
        $namespace = $separator === false ? '' : substr($name, 0, $separator);
        $docComment = $original->getDocComment();
        if (!$this->visibilityDetector->isRestricted($docComment === null ? null : $docComment->getText(), $namespace)) {
            return [];
        }

        $variants = $function->getVariants();
        if (!isset($variants[0])) {
            return [];
        }

        return $this->signatureErrors($variants[0], $original, sprintf('%s()', $name));
    }

    /**
     * Collects errors from an always-internal closure or arrow function.
     *
     * @return list<IdentifierRuleError>
     */
    public function closure(InClosureNode|InArrowFunctionNode $node): array
    {
        return $this->signatureErrors($node->getClosureType(), $node->getOriginalNode(), 'anonymous function');
    }

    /**
     * Collects concrete mixed from one resolved callable variant.
     *
     * @return list<IdentifierRuleError>
     */
    public function signatureErrors(
        ParametersAcceptor $variant,
        \PhpParser\Node\FunctionLike $node,
        string $symbol,
        ?ClassReflection $class = null,
        ?string $methodName = null,
    ): array {
        $errors = [];
        foreach ($variant->getParameters() as $position => $parameter) {
            $type = $parameter->getType();
            if (!$this->typeInspector->contains($type)) {
                continue;
            }
            if ($class !== null && $methodName !== null && $this->contractInspector->allowsParameter($class, $methodName, $position)) {
                continue;
            }
            $params = $node->getParams();
            $line = isset($params[$position]) ? $params[$position]->getStartLine() : $node->getStartLine();
            $errors[] = $this->errorBuilder->build($this->typeInspector->describe($type), 'parameter $' . $parameter->getName(), $symbol, $line);
        }

        return $this->appendReturnError($errors, $variant, $node, $symbol, $class, $methodName);
    }

    /**
     * Adds a return error when the callable explicitly declares one.
     *
     * @param list<IdentifierRuleError> $errors
     * @return list<IdentifierRuleError>
     */
    public function appendReturnError(
        array $errors,
        ParametersAcceptor $variant,
        \PhpParser\Node\FunctionLike $node,
        string $symbol,
        ?ClassReflection $class,
        ?string $methodName,
    ): array {
        $returnType = $variant->getReturnType();
        if (!$this->hasReturnDeclaration($node) || !$this->typeInspector->contains($returnType)) {
            return $errors;
        }
        if ($class !== null && $methodName !== null && $this->contractInspector->allowsReturn($class, $methodName)) {
            return $errors;
        }

        $errors[] = $this->errorBuilder->build($this->typeInspector->describe($returnType), 'return type', $symbol, $node->getStartLine());

        return $errors;
    }

    /**
     * Reports whether a callable writes a native or PHPDoc return declaration.
     */
    public function hasReturnDeclaration(\PhpParser\Node\FunctionLike $node): bool
    {
        if ($node->getReturnType() !== null) {
            return true;
        }

        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return false;
        }

        $text = $docComment->getText();

        return str_contains($text, '@return') || str_contains($text, '@phpstan-return') || str_contains($text, '@psalm-return');
    }

    /**
     * Reports magic methods whose callable shape is a PHP language protocol.
     */
    public function isMagicProtocol(string $methodName): bool
    {
        $normalized = strtolower($methodName);

        return $this->magicMethods->isMagic($normalized)
            && $normalized !== '__construct'
            && $normalized !== '__destruct'
            && $normalized !== '__clone';
    }
}
