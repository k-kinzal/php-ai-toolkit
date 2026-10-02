<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Layer;

use function preg_match;
use function str_replace;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;

/**
 * Assigns documented classes to deptrac layers.
 *
 * The directory collector matches the project-relative file path and the
 * class name collectors match the fully qualified name, mirroring deptrac.
 */
final class LayerAssigner
{
    /**
     * Returns the names of all layers a class-like symbol belongs to.
     *
     * @return list<string>
     */
    public function assign(LayerModel $model, ClassLikeDoc $classLike): array
    {
        $names = [];
        foreach ($model->layers as $layer) {
            foreach ($layer->collectors as $collector) {
                if ($this->matches($collector, $classLike)) {
                    $names[] = $layer->name;
                    break;
                }
            }
        }

        return $names;
    }

    /**
     * Reports whether one collector matches a class-like symbol.
     */
    public function matches(LayerCollector $collector, ClassLikeDoc $classLike): bool
    {
        if ($collector->type === 'directory') {
            return @preg_match($this->pattern($collector->value), $classLike->file) === 1;
        }

        if ($collector->type === 'className' || $collector->type === 'classNameRegex' || $collector->type === 'namespace') {
            return @preg_match($this->pattern($collector->value), $classLike->fqcn) === 1;
        }

        return false;
    }

    /**
     * Wraps a deptrac collector value into a delimited regex pattern.
     */
    public function pattern(string $value): string
    {
        return '#' . str_replace('#', '\#', $value) . '#';
    }
}
