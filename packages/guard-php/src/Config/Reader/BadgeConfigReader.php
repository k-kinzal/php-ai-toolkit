<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use function array_values;

use Guard\Config\Validation\HeadingConfigKeyValidator;
use Guard\Config\Value\BadgeEntry;
use Guard\Policy\PolicyException;

use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

/**
 * Reads the ordered badge declarations of a document.
 *
 * Each entry names a badge and gives a glob pattern for its image URL. A badge is required exactly once unless
 * "optional" or "repeat" is set; "label" additionally fixes its alternative text.
 */
final class BadgeConfigReader
{
    /**
     * Reads a non-empty list of badge declarations with unique names.
     *
     * @param mixed $value
     * @return list<BadgeEntry>
     *
     * @throws PolicyException when the list or an entry is invalid
     */
    public function read($value, string $context): array
    {
        $label = $context . '.badges';
        if (!is_array($value) || $value === [] || array_values($value) !== $value) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a non-empty list of badges such as {name: PHP, image: \'https://img.shields.io/badge/php-*\'}.', $label));
        }
        $badges = [];
        $names = [];
        foreach ($value as $index => $entry) {
            $badge = $this->entry($entry, sprintf('%s[%d]', $label, $index));
            if (isset($names[$badge->name])) {
                throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" declares the badge "%s" more than once. Use "repeat: true" for a badge that may occur several times.', $label, $badge->name));
            }
            $names[$badge->name] = true;
            $badges[] = $badge;
        }

        return $badges;
    }

    /**
     * Reads one badge declaration.
     *
     * @param mixed $entry
     *
     * @throws PolicyException when a key is unknown or a value has the wrong type
     */
    public function entry($entry, string $context): BadgeEntry
    {
        if (!is_array($entry) || ($entry !== [] && array_values($entry) === $entry)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s" must be a mapping with "name" and "image".', $context));
        }
        (new HeadingConfigKeyValidator())->rejectUnknown($entry, ['name', 'image', 'label', 'optional', 'repeat'], $context);
        $name = $entry['name'] ?? null;
        $image = $entry['image'] ?? null;
        if (!is_string($name) || $name === '' || !is_string($image) || $image === '') {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.name" and "%s.image" must be non-empty strings.', $context, $context));
        }
        $label = $entry['label'] ?? null;
        if ($label !== null && !is_string($label)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.label" must be a string.', $context));
        }
        $optional = $entry['optional'] ?? false;
        $repeat = $entry['repeat'] ?? false;
        if (!is_bool($optional) || !is_bool($repeat)) {
            throw new PolicyException(sprintf('Invalid doc-guard.yaml: "%s.optional" and "%s.repeat" must be true or false.', $context, $context));
        }

        return new BadgeEntry($name, $image, $label, $optional, $repeat);
    }
}
