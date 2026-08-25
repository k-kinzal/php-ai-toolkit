<?php

declare(strict_types=1);

namespace Toolkit\Doctest\Scanner;

use function basename;

/**
 * Represents a target (file, class, method, or function) that may contain docblock examples.
 *
 * A Target encapsulates all information needed to identify and process a documented
 * element in the source code, including its type, location, and associated docblock.
 *
 * @property-read string $type
 * @property-read string $filePath
 * @property-read string $docblock
 * @property-read string $name
 * @property-read int $line
 * @property-read ?string $namespace
 * @property-read ?string $className
 * @property-read bool $isStatic
 */
final class Target
{
    /**
     * @param string $type the kind of this target, one of the TargetKind constants
     * @param string $filePath absolute path to the source file
     * @param string $docblock the raw docblock content including delimiters
     * @param string $name the name of the target, such as a class or method name
     * @param int $line line number where the docblock starts
     * @param string|null $namespace the namespace of the target, if applicable
     * @param string|null $className the class name, if the target is a method
     * @param bool $isStatic whether the target is a static method
     */
    public function __construct(
        /** @readonly */
        private string $type,
        /** @readonly */
        private string $filePath,
        /** @readonly */
        private string $docblock,
        /** @readonly */
        private string $name,
        /** @readonly */
        private int $line,
        /** @readonly */
        private ?string $namespace = null,
        /** @readonly */
        private ?string $className = null,
        /** @readonly */
        private bool $isStatic = false,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'type' => $this->type,
            'filePath' => $this->filePath,
            'docblock' => $this->docblock,
            'name' => $this->name,
            'line' => $this->line,
            'namespace' => $this->namespace,
            'className' => $this->className,
            'isStatic' => $this->isStatic,
            default => null,
        };
    }

    /**
     * Returns the fully qualified name of the target.
     */
    public function getFullyQualifiedName(): string
    {
        $prefix = $this->namespace !== null ? $this->namespace . '\\' : '';
        if ($this->type === TargetKind::FILE) {
            return $this->filePath;
        }

        if ($this->type === TargetKind::CLASS_LIKE) {
            return $prefix . $this->name;
        }

        if ($this->type === TargetKind::FUNCTION) {
            return $prefix . $this->name . '()';
        }

        return $prefix . $this->className . '::' . $this->name . '()';
    }

    /**
     * Returns the short (unqualified) name of the target.
     */
    public function getShortName(): string
    {
        if ($this->type === TargetKind::FILE) {
            return basename($this->filePath);
        }

        if ($this->type === TargetKind::CLASS_LIKE) {
            return $this->name;
        }

        if ($this->type === TargetKind::FUNCTION) {
            return $this->name . '()';
        }

        return $this->className . '::' . $this->name . '()';
    }
}
