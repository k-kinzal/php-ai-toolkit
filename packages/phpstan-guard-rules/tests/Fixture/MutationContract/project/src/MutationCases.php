<?php

declare(strict_types=1);

namespace Tests\Fixture\MutationContract;

final class Box
{
    public int $value = 0;

    /** @mutation $this */
    public function bump(): void
    {
        ++$this->value;
    }
}

final class MutationCases
{
    public int $count = 0;

    /**
     * @param Box $box +mut value to update
     * @mutation $this, global
     */
    public function allowed(Box $box): void
    {
        ++$box->value;
        ++$this->count;
        $GLOBALS['mutation_allowed'] = true;
    }

    /** @param Box $box */
    public function directArgument(Box $box): void
    {
        ++$box->value;
    }

    public function directThis(): void
    {
        ++$this->count;
    }

    public function directGlobal(): void
    {
        $GLOBALS['mutation_direct'] = true;
    }

    /** @param Box $box */
    public function transfersArgument(Box $box): void
    {
        $box->bump();
    }

    public function transfersThis(): void
    {
        $this->mutateSelf();
    }

    /** @mutation $this */
    public function mutateSelf(): void
    {
        ++$this->count;
    }

    public function transfersGlobal(): void
    {
        mutate_global();
    }

    /** @param Box $box */
    public function aliasesArgument(Box $box): void
    {
        $alias = $box;
        ++$alias->value;
    }
}

/** @mutation global */
function mutate_global(): void
{
    $GLOBALS['mutation_function'] = true;
}

/** @param Box $box +mut */
function mutate_box(Box $box): void
{
    ++$box->value;
}

/** @param Box $box */
function passes_box(Box $box): void
{
    mutate_box($box);
}

/** @param Box $box description +mut */
function malformed(Box $box): void
{
}

interface ReadOnlyContract
{
    /** @param Box $box */
    public function work(Box $box): void;
}

final class WideningImplementation implements ReadOnlyContract
{
    /** @param Box $box +mut */
    public function work(Box $box): void
    {
        ++$box->value;
    }
}

interface MutableContract
{
    /** @param Box $box +mut */
    public function inherited(Box $box): void;
}

final class InheritedImplementation implements MutableContract
{
    public function inherited(Box $box): void
    {
        ++$box->value;
    }
}

final class ConstructorInitialization
{
    public int $value;

    public function __construct()
    {
        $this->value = 1;
    }
}

/** @param Box $box */
function passes_box_transitively(Box $box): void
{
    passes_box($box);
}

/** @param Box $box */
function reassigns_box(Box $box): void
{
    $box = new Box();
}

function changes_static_cache(): void
{
    static $cache = 0;
    ++$cache;
}
