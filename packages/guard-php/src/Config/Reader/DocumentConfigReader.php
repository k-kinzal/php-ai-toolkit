<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_key_exists;
use function array_values;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Value\DeclaredHeading;
use Guard\Config\Value\DocumentConfig;
use Guard\Policy\PolicyException;

use function is_array;
use function is_int;
use function is_string;
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
     * Reads the "headings" list, the optional "max_level", and the optional "outlines", "badges", and "content" checks.
     *
     * @param mixed $value
     *
     * @throws PolicyException when the entry is not a mapping, has unsupported keys, or declares invalid checks
     */
    public function read(string $path, $value): DocumentConfig
    {
        $context = sprintf('documents.%s', $path);
        if (!is_array($value) || $value !== [] && array_values($value) === $value) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a mapping with a "headings" list.', $context));
        }

        $this->keyValidator->rejectUnknown($value, ['headings', 'max_level', 'outlines', 'badges', 'content'], $context);

        $maxLevel = $value['max_level'] ?? 6;
        if (!is_int($maxLevel) || $maxLevel < 1 || $maxLevel > 6) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.max_level" must be an integer from 1 to 6.', $context));
        }
        $outlines = array_key_exists('outlines', $value) ? (new OutlineConfigReader())->read($value['outlines'], $context) : [];
        $badges = array_key_exists('badges', $value) ? (new BadgeConfigReader())->read($value['badges'], $context) : null;
        $content = $value['content'] ?? null;
        if ($content !== null && !is_string($content)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.content" must be a string holding the exact file content.', $context));
        }
        if (!array_key_exists('headings', $value) && ($outlines !== [] || $badges !== null || $content !== null)) {
            return new DocumentConfig($path, null, $maxLevel, $outlines, $badges, $content);
        }

        return new DocumentConfig($path, $this->headings($value['headings'] ?? null, $maxLevel, $context), $maxLevel, $outlines, $badges, $content);
    }

    /**
     * Reads the exact heading list, each no deeper than the maximum level.
     *
     * @param mixed $entries
     * @return list<DeclaredHeading>
     *
     * @throws PolicyException when the value is not a list of headings or a heading is too deep
     */
    public function headings($entries, int $maxLevel, string $context): array
    {
        if (!is_array($entries) || array_values($entries) !== $entries) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.headings" must be a list of headings such as \'## Usage\'; use [] for a document without headings, or declare "outlines", "badges", or "content" instead.', $context));
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

        return $headings;
    }
}
