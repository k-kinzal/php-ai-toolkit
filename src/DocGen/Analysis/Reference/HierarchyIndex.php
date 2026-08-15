<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;

use function sort;
use function strtolower;

/**
 * Answers inheritance questions across all documented class-like symbols.
 */
final class HierarchyIndex
{
    /** @var array<string, list<string>> */
    private array $subclasses = [];

    /** @var array<string, list<string>> */
    private array $implementors = [];

    /** @var array<string, list<string>> */
    private array $interfaceExtenders = [];

    /** @var array<string, list<string>> */
    private array $traitUsers = [];

    /**
     * Indexes the parent relations of the given class-like symbols.
     *
     * @param list<ClassLikeDoc> $classLikes
     */
    public function build(array $classLikes): void
    {
        foreach ($classLikes as $classLike) {
            foreach ($classLike->extends as $parent) {
                if ($classLike->kind === ClassLikeKind::INTERFACE_) {
                    $this->interfaceExtenders[strtolower($parent)][] = $classLike->fqcn;
                } else {
                    $this->subclasses[strtolower($parent)][] = $classLike->fqcn;
                }
            }

            foreach ($classLike->implements as $interface) {
                $this->implementors[strtolower($interface)][] = $classLike->fqcn;
            }

            foreach ($classLike->traits as $trait) {
                $this->traitUsers[strtolower($trait)][] = $classLike->fqcn;
            }
        }
    }

    /**
     * Returns the documented subclasses of a class.
     *
     * @return list<string>
     */
    public function subclassesOf(string $fqcn): array
    {
        $result = $this->subclasses[strtolower($fqcn)] ?? [];
        sort($result);

        return $result;
    }

    /**
     * Returns the documented implementors of an interface.
     *
     * @return list<string>
     */
    public function implementorsOf(string $fqcn): array
    {
        $result = $this->implementors[strtolower($fqcn)] ?? [];
        sort($result);

        return $result;
    }

    /**
     * Returns the documented interfaces extending an interface.
     *
     * @return list<string>
     */
    public function interfaceExtendersOf(string $fqcn): array
    {
        $result = $this->interfaceExtenders[strtolower($fqcn)] ?? [];
        sort($result);

        return $result;
    }

    /**
     * Returns the documented users of a trait.
     *
     * @return list<string>
     */
    public function traitUsersOf(string $fqcn): array
    {
        $result = $this->traitUsers[strtolower($fqcn)] ?? [];
        sort($result);

        return $result;
    }
}
