<?php

declare(strict_types=1);

namespace Guard\Collect;

use Guard\Collect\Filesystem\Discovery;
use Guard\Collect\Filesystem\Filesystem;
use Guard\Collect\Filesystem\NativeFilesystem;
use Guard\Collect\Filesystem\Snapshot;
use Guard\Structure\Source;
use Guard\Structure\Structurer;
use JsonException;
use RuntimeException;

/**
 * Selects files once and structures each physical file once per requested format.
 */
final class Collector
{
    /**
     * Creates the Collector with its declared dependencies.
     */
    public function __construct(private ?Filesystem $filesystem = null)
    {
    }
    /** Merges every policy's requests before filesystem access; metadata-only inputs never read bytes.
     * @param array<string, Input> $inputs
     * @param array<string, Structurer> $structurers
     * @return array<string, FileSet>
     */
    public function collect(string $root, array $inputs, array $structurers): array
    {
        $snapshot = new Snapshot($this->filesystem ?? new NativeFilesystem());
        $selections = (new Discovery($snapshot))->discover($root, $inputs);
        /** @var array<string, list<array{string, int|string, FileRecord}>> $demands */
        $demands = [];
        foreach ($selections as $id => $selection) {
            foreach ($selection->files() as $key => $file) {
                $demands[$file->entry->identity][] = [$id, $key, $file];
            }
        }
        /** @var array<string, array<array-key, StructuredFile>> $values */
        $values = [];
        foreach ($demands as $requests) {
            $source = null;
            $read = false;
            foreach ($requests as [$id, $key, $file]) {
                $format = $inputs[$id]->structure;
                if ($file->entry->file && $format !== null && !$read) {
                    $bytes = $snapshot->read($file->path);
                    $source = $bytes === false ? null : new Source($bytes, $structurers);
                    $read = true;
                }
                $data = null;
                $failure = null;
                if ($source !== null && $format !== null) {
                    try {
                        $data = $source->structure($format);
                    } catch (RuntimeException|JsonException|\Nette\Neon\Exception $error) {
                        $failure = $error;
                    }
                }
                $values[$id][$key] = new StructuredFile($file, $format === null || $source !== null, $data, $failure);
            }
        }
        $result = [];
        foreach ($selections as $id => $selection) {
            /**
             * Restore each query's established path order after physical-file grouping.
             */
            $files = [];
            foreach ($selection->files() as $key => $file) {
                $files[$key] = $values[$id][$key];
            }
            $result[$id] = new FileSet($files, $selection->directories(), $selection->failure());
        }
        return $result;
    }
}
