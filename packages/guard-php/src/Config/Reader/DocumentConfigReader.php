<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_key_exists;
use function array_values;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Value\DocumentConfig;
use Guard\Policy\PolicyException;

use function is_array;
use function is_int;
use function sprintf;

/**
 * Reads the declared structure of one document from doc-guard.yaml.
 */
final class DocumentConfigReader
{
    /** @readonly */
    private HeadingConfigKeyValidator $keyValidator;

    /** @readonly */
    private DeclaredHeadingReader $headingReader;

    /**
     * Creates a reader from key validation and heading parsing.
     */
    public function __construct(?HeadingConfigKeyValidator $keyValidator = null, ?DeclaredHeadingReader $headingReader = null)
    {
        $this->keyValidator = $keyValidator ?? new HeadingConfigKeyValidator();
        $this->headingReader = $headingReader ?? new DeclaredHeadingReader();
    }

    /**
     * Reads the "headings" list and the optional "max_level" of a document.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the entry is not a mapping, has unsupported keys, or declares invalid headings
     */
    public function read(string $path, $value): DocumentConfig
    {
        $context = sprintf('documents.%s', $path);
        if (!is_array($value) || $value !== [] && array_values($value) === $value) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a mapping with a "headings" list.', $context));
        }

        $this->keyValidator->rejectUnknown($value, ['headings', 'max_level'], $context);

        $maxLevel = $value['max_level'] ?? 6;
        if (!is_int($maxLevel) || $maxLevel < 1 || $maxLevel > 6) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.max_level" must be an integer from 1 to 6.', $context));
        }

        $entries = array_key_exists('headings', $value) ? $value['headings'] : null;
        if (!is_array($entries) || array_values($entries) !== $entries) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.headings" must be a list of headings such as \'## Usage\'; use [] for a document without headings.', $context));
        }

        $headings = [];
        foreach ($entries as $index => $entry) {
            $label = sprintf('%s.headings[%d]', $context, $index);
            $heading = $this->headingReader->read($entry, $label);
            if ($heading->level > $maxLevel) {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" declares "%s", which is deeper than "%s.max_level" (%d).', $label, $heading->notation(), $context, $maxLevel));
            }
            $headings[] = $heading;
        }

        return new DocumentConfig($path, $headings, $maxLevel);
    }
}
