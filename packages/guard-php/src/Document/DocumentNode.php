<?php

declare(strict_types=1);

namespace Guard\Document;

use Guard\Policy\PolicyException;
use SplObjectStorage;
use stdClass;

/**
 * Stores a parsed document tree while preserving map, list and scalar types.
 */
final class DocumentNode
{
    private bool $objectMap = false;

    /** @var ?array<array-key, DocumentNode> */
    private ?array $children = null;

    private bool|int|float|string|object|null $scalar = null;

    /**
     * Structures values returned by the supported configuration parsers.
     * @param ?SplObjectStorage<object, DocumentNode> $objects preserves parser alias relationships
     * @throws PolicyException when a parser returns a resource
     */
    public function __construct(mixed $value, ?SplObjectStorage $objects = null)
    {
        if ($objects === null) {
            /** @var SplObjectStorage<object, DocumentNode> $objects */
            $objects = new SplObjectStorage();
        }
        if (is_array($value) || $value instanceof stdClass) {
            $this->objectMap = $value instanceof stdClass;
            if ($value instanceof stdClass) {
                $objects[$value] = $this;
            }
            $this->children = [];
            foreach (is_array($value) ? $value : get_object_vars($value) as $key => $child) {
                $this->children[$key] = $child instanceof stdClass && isset($objects[$child])
                    ? $objects[$child] : new self($child, $objects);
            }
        } elseif (is_scalar($value) || is_object($value) || $value === null) {
            $this->scalar = $value;
        } else {
            throw new PolicyException('A configuration parser returned a resource. Return document scalars, arrays or objects instead.');
        }
    }

    /**
     * Returns independent containers for selection and repair evaluation.
     * @param ?SplObjectStorage<DocumentNode, stdClass> $objects preserves aliases within each copy
     */
    public function native(?SplObjectStorage $objects = null): mixed
    {
        if ($objects === null) {
            /** @var SplObjectStorage<DocumentNode, stdClass> $objects */
            $objects = new SplObjectStorage();
        }
        if ($this->children === null) {
            return $this->scalar;
        }
        if ($this->objectMap) {
            if (isset($objects[$this])) {
                return $objects[$this];
            }
            $result = new stdClass();
            $objects[$this] = $result;
            foreach ($this->children as $key => $child) {
                $result->{$key} = $child->native($objects);
            }
            return $result;
        }
        $result = [];
        foreach ($this->children as $key => $child) {
            $result[$key] = $child->native($objects);
        }
        return $result;
    }
}
