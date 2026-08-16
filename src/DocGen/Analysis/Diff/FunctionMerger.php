<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;

/**
 * Merges the two revisions of one top-level function.
 */
final class FunctionMerger
{
    /** @readonly */
    private ParameterMerger $parameters;

    /** @readonly */
    private SymbolFingerprint $fingerprint;

    /** @readonly */
    private ClassLikeMerger $parts;

    /**
     * Creates a function merger from its parameter collaborators.
     */
    public function __construct(?ParameterMerger $parameters = null, ?SymbolFingerprint $fingerprint = null, ?ClassLikeMerger $parts = null)
    {
        $this->parameters = $parameters ?? new ParameterMerger();
        $this->fingerprint = $fingerprint ?? new SymbolFingerprint();
        $this->parts = $parts ?? new ClassLikeMerger();
    }

    /**
     * Merges a function that both revisions declare.
     */
    public function merge(FunctionDoc $base, FunctionDoc $head, DiffIndex $index): FunctionDoc
    {
        $status = $this->fingerprint->functionSymbol($base) === $this->fingerprint->functionSymbol($head)
            ? DiffStatus::SAME
            : DiffStatus::MODIFIED;
        $key = $index->keys()->functionSymbol($head->fqn);
        $index->mark($key, $status);
        $this->markParts($base, $head, $status, $key, $index);

        return $this->rebuild($head, $this->parameters->merge($base->parameters, $head->parameters, $key, $index));
    }

    /**
     * Records the state of the return type and the throws of a function.
     *
     * @param ?FunctionDoc $base the function as the base revision had it
     * @param string $status the state of the function itself
     * @param string $key the diff key of the function
     */
    public function markParts(?FunctionDoc $base, FunctionDoc $head, string $status, string $key, DiffIndex $index): void
    {
        $index->mark($index->keys()->returnType($key), $this->parts->partStatus(
            $base !== null ? $this->fingerprint->type($base->returnType) : null,
            $this->fingerprint->type($head->returnType),
            $status,
        ));
        $index->mark($index->keys()->throwsTags($key), $this->parts->partStatus(
            $base !== null ? $this->fingerprint->throwsTags($base->docBlock) : null,
            $this->fingerprint->throwsTags($head->docBlock),
            $status,
        ));
    }

    /**
     * Marks a function only one revision declares.
     *
     * @param string $status the state every part of the function is in
     */
    public function single(FunctionDoc $function, string $status, DiffIndex $index): FunctionDoc
    {
        $key = $index->keys()->functionSymbol($function->fqn);
        $index->mark($key, $status);
        $this->markParts(null, $function, $status, $key, $index);
        $removed = $status === DiffStatus::REMOVED;
        $parameters = $this->parameters->merge(
            $removed ? $function->parameters : [],
            $removed ? [] : $function->parameters,
            $key,
            $index,
        );

        return $this->rebuild($function, $parameters);
    }

    /**
     * Rebuilds one function around its merged parameter list.
     *
     * @param list<\PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc> $parameters
     */
    public function rebuild(FunctionDoc $function, array $parameters): FunctionDoc
    {
        return new FunctionDoc(
            $function->fqn,
            $function->shortName,
            $function->namespace,
            $function->packageName,
            $function->file,
            $function->startLine,
            $function->endLine,
            $parameters,
            $function->returnType,
            $function->docBlock,
            $function->useMap,
            $function->isDev,
        );
    }
}
