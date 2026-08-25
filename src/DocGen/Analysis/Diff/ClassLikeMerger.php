<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Diff;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\ConstantDoc;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\PropertyDoc;

/**
 * Merges the two revisions of one class-like symbol into one page model.
 *
 * The merged symbol carries what the head declares plus what the base
 * declared and the head dropped, so a page can show a removal instead of
 * silently losing it, and every part of it is marked in the diff index.
 */
final class ClassLikeMerger
{
    /** @readonly */
    private MemberMerger $members;

    /** @readonly */
    private ParameterMerger $parameters;

    /** @readonly */
    private SymbolFingerprint $fingerprint;

    /** @readonly */
    private DiffStatus $status;

    /**
     * Creates a class-like merger from its member collaborators.
     */
    public function __construct(
        ?MemberMerger $members = null,
        ?ParameterMerger $parameters = null,
        ?SymbolFingerprint $fingerprint = null,
        ?DiffStatus $status = null,
    ) {
        $this->members = $members ?? new MemberMerger();
        $this->parameters = $parameters ?? new ParameterMerger();
        $this->fingerprint = $fingerprint ?? new SymbolFingerprint();
        $this->status = $status ?? new DiffStatus();
    }

    /**
     * Merges a symbol that both revisions declare.
     */
    public function merge(ClassLikeDoc $base, ClassLikeDoc $head, DiffIndex $index): ClassLikeDoc
    {
        $merged = $this->rebuild(
            $head,
            $this->constants($base, $head, $index),
            $this->properties($base, $head, $index),
            $this->methods($base, $head, $index),
            $this->enumCases($base, $head, $index),
        );
        $header = $this->fingerprint->classHeader($base) === $this->fingerprint->classHeader($head)
            ? DiffStatus::SAME
            : DiffStatus::MODIFIED;
        $index->mark($index->keys()->header($head->fqcn), $header);
        $index->mark($index->keys()->classLike($head->fqcn), $this->statusOf($merged, $header, $index));

        return $merged;
    }

    /**
     * Marks a symbol only one revision declares and merges its parameters.
     *
     * @param string $status the state every part of the symbol is in
     */
    public function single(ClassLikeDoc $classLike, string $status, DiffIndex $index): ClassLikeDoc
    {
        $keys = $index->keys();
        $index->mark($keys->classLike($classLike->fqcn), $status);
        $index->mark($keys->header($classLike->fqcn), $status);
        foreach ($classLike->constants as $constant) {
            $index->mark($keys->member($classLike->fqcn, DiffKey::CONSTANT, $constant->name), $status);
        }

        foreach ($classLike->properties as $property) {
            $index->mark($keys->member($classLike->fqcn, DiffKey::PROPERTY, $property->name), $status);
        }

        foreach ($classLike->enumCases as $case) {
            $index->mark($keys->member($classLike->fqcn, DiffKey::ENUM_CASE, $case->name), $status);
        }

        $methods = [];
        foreach ($classLike->methods as $method) {
            $key = $keys->member($classLike->fqcn, DiffKey::METHOD, $method->name);
            $index->mark($key, $status);
            $methods[] = $this->mergedMethod(null, $method, $status, $key, $index);
        }

        return $this->rebuild($classLike, $classLike->constants, $classLike->properties, $methods, $classLike->enumCases);
    }

    /**
     * Combines the state of every part of a symbol into its own state.
     *
     * @param string $headerStatus the state of the declaration head
     */
    public function statusOf(ClassLikeDoc $merged, string $headerStatus, DiffIndex $index): string
    {
        $keys = $index->keys();
        $statuses = [$headerStatus];
        foreach ($merged->constants as $constant) {
            $statuses[] = $index->status($keys->member($merged->fqcn, DiffKey::CONSTANT, $constant->name));
        }

        foreach ($merged->properties as $property) {
            $statuses[] = $index->status($keys->member($merged->fqcn, DiffKey::PROPERTY, $property->name));
        }

        foreach ($merged->methods as $method) {
            $statuses[] = $index->status($keys->member($merged->fqcn, DiffKey::METHOD, $method->name));
        }

        foreach ($merged->enumCases as $case) {
            $statuses[] = $index->status($keys->member($merged->fqcn, DiffKey::ENUM_CASE, $case->name));
        }

        return $this->status->combine($statuses);
    }

    /**
     * Merges the class constants of two revisions.
     *
     * @return list<ConstantDoc>
     */
    public function constants(ClassLikeDoc $base, ClassLikeDoc $head, DiffIndex $index): array
    {
        $constants = [];
        $merged = $this->members->merge(
            $base->constants,
            $head->constants,
            static fn (ConstantDoc $constant): string => $constant->name,
            fn (ConstantDoc $constant): string => $this->fingerprint->constant($constant),
        );
        foreach ($merged as $entry) {
            $index->mark($index->keys()->member($head->fqcn, DiffKey::CONSTANT, $entry['item']->name), $entry['status']);
            $constants[] = $entry['item'];
        }

        return $constants;
    }

    /**
     * Merges the properties of two revisions.
     *
     * @return list<PropertyDoc>
     */
    public function properties(ClassLikeDoc $base, ClassLikeDoc $head, DiffIndex $index): array
    {
        $properties = [];
        $merged = $this->members->merge(
            $base->properties,
            $head->properties,
            static fn (PropertyDoc $property): string => $property->name,
            fn (PropertyDoc $property): string => $this->fingerprint->property($property),
        );
        foreach ($merged as $entry) {
            $index->mark($index->keys()->member($head->fqcn, DiffKey::PROPERTY, $entry['item']->name), $entry['status']);
            $properties[] = $entry['item'];
        }

        return $properties;
    }

    /**
     * Merges the enum cases of two revisions.
     *
     * @return list<EnumCaseDoc>
     */
    public function enumCases(ClassLikeDoc $base, ClassLikeDoc $head, DiffIndex $index): array
    {
        $cases = [];
        $merged = $this->members->merge(
            $base->enumCases,
            $head->enumCases,
            static fn (EnumCaseDoc $case): string => $case->name,
            fn (EnumCaseDoc $case): string => $this->fingerprint->enumCase($case),
        );
        foreach ($merged as $entry) {
            $index->mark($index->keys()->member($head->fqcn, DiffKey::ENUM_CASE, $entry['item']->name), $entry['status']);
            $cases[] = $entry['item'];
        }

        return $cases;
    }

    /**
     * Merges the methods of two revisions, parameter lists included.
     *
     * @return list<MethodDoc>
     */
    public function methods(ClassLikeDoc $base, ClassLikeDoc $head, DiffIndex $index): array
    {
        $name = static fn (MethodDoc $method): string => $method->name;
        $merged = $this->members->merge(
            $base->methods,
            $head->methods,
            $name,
            fn (MethodDoc $method): string => $this->fingerprint->method($method),
        );
        $methods = [];
        foreach ($merged as $entry) {
            $key = $index->keys()->member($head->fqcn, DiffKey::METHOD, $entry['item']->name);
            $index->mark($key, $entry['status']);
            $counterpart = $this->members->find($base->methods, $entry['item']->name, $name);
            $methods[] = $this->mergedMethod($counterpart, $entry['item'], $entry['status'], $key, $index);
        }

        return $methods;
    }

    /**
     * Rebuilds one method around its merged parameter list.
     *
     * @param ?MethodDoc $counterpart the method as the base revision had it
     * @param string $status the state of the method itself
     * @param string $key the diff key the parameters are recorded under
     */
    public function mergedMethod(?MethodDoc $counterpart, MethodDoc $method, string $status, string $key, DiffIndex $index): MethodDoc
    {
        $removed = $status === DiffStatus::REMOVED;
        $base = $removed ? $method->parameters : ($counterpart !== null ? $counterpart->parameters : []);
        $parameters = $this->parameters->merge($base, $removed ? [] : $method->parameters, $key, $index);
        $index->mark($index->keys()->returnType($key), $this->partStatus(
            $counterpart !== null ? $this->fingerprint->type($counterpart->returnType) : null,
            $this->fingerprint->type($method->returnType),
            $status,
        ));
        $index->mark($index->keys()->throwsTags($key), $this->partStatus(
            $counterpart !== null ? $this->fingerprint->throwsTags($counterpart->docBlock) : null,
            $this->fingerprint->throwsTags($method->docBlock),
            $status,
        ));

        return new MethodDoc(
            $method->name,
            $method->visibility,
            $method->isStatic,
            $method->isAbstract,
            $method->isFinal,
            $parameters,
            $method->returnType,
            $method->docBlock,
            $method->startLine,
            $method->endLine,
        );
    }

    /**
     * Determines the state of one part of a declaration.
     *
     * A part of a member the revision added or dropped is in the state of
     * the member itself; otherwise it is compared on its own, so a method
     * whose return type changed says which part of it changed.
     *
     * @param ?string $base the part as the base revision had it
     * @param string $head the part as the head revision has it
     * @param string $memberStatus the state of the member the part belongs to
     */
    public function partStatus(?string $base, string $head, string $memberStatus): string
    {
        if ($memberStatus === DiffStatus::ADDED || $memberStatus === DiffStatus::REMOVED) {
            return $memberStatus;
        }

        return $base === $head ? DiffStatus::SAME : DiffStatus::MODIFIED;
    }

    /**
     * Rebuilds one class-like symbol around merged member lists.
     *
     * @param list<ConstantDoc> $constants
     * @param list<PropertyDoc> $properties
     * @param list<MethodDoc> $methods
     * @param list<EnumCaseDoc> $enumCases
     */
    public function rebuild(ClassLikeDoc $classLike, array $constants, array $properties, array $methods, array $enumCases): ClassLikeDoc
    {
        return new ClassLikeDoc(
            $classLike->fqcn,
            $classLike->shortName,
            $classLike->namespace,
            $classLike->kind,
            $classLike->packageName,
            $classLike->file,
            $classLike->startLine,
            $classLike->endLine,
            $classLike->isAbstract,
            $classLike->isFinal,
            $classLike->extends,
            $classLike->implements,
            $classLike->traits,
            $constants,
            $properties,
            $methods,
            $enumCases,
            $classLike->backingType,
            $classLike->docBlock,
            $classLike->useMap,
            $classLike->isDev,
        );
    }
}
