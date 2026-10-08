<?php

declare(strict_types=1);

namespace Guard\Policy\Diagnostic;

use function array_map;
use function count;

use Guard\Policy\Definition\BadgeEntry;
use Guard\Structure\Markdown\Badge\Badge;
use Guard\Structure\Markdown\Heading;

use function implode;
use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use function sprintf;

/**
 * Builds outline, badge, and content violations with messages that name the change and its fix.
 */
final class DocumentViolationFactory
{
    /**
     * Reports a heading that does not fit the closest declared outline at its position.
     *
     * @param list<string> $expected descriptions of the headings the outline allows at that position
     * @param list<string> $outlines the names of all declared outlines
     */
    public function unexpectedOutlineHeading(string $path, Heading $actual, array $expected, string $outline, array $outlines, string $configName): HeadingViolation
    {
        return new HeadingViolation($path, $actual->line, 'unexpected_outline_heading', $expected === [] ? null : implode(' or ', $expected), $actual->notation(), sprintf(
            'Heading "%s" does not follow outline "%s"%s declared for %s in %s; %s. Rename, move, or remove the heading so the document follows the outline; changing the outline requires a human to update %s.',
            $actual->notation(),
            $outline,
            $this->alternatives($outlines),
            $path,
            $configName,
            $expected === [] ? 'no further heading is allowed at that position' : 'expected ' . implode(' or ', $expected) . ' at that position',
            $configName,
        ));
    }

    /**
     * Reports a heading the closest declared outline requires but the document lacks.
     *
     * @param list<string> $expected the headings that can fill the position, in ATX notation
     * @param list<string> $outlines the names of all declared outlines
     */
    public function missingOutlineHeading(string $path, array $expected, string $position, string $outline, array $outlines, string $configName): HeadingViolation
    {
        return new HeadingViolation($path, null, 'missing_outline_heading', implode(' or ', $expected), null, sprintf(
            'Heading %s required by outline "%s"%s is missing from %s; %s declares it %s. Add the section at that position; changing the outline requires a human to update %s.',
            implode(' or ', array_map(static fn (string $heading): string => '"' . $heading . '"', $expected)),
            $outline,
            $this->alternatives($outlines),
            $path,
            $configName,
            $position,
            $configName,
        ));
    }

    /**
     * Reports a document without a badge block directly below its title.
     *
     * @param list<BadgeEntry> $declared
     */
    public function missingBadges(string $path, ?Heading $title, ?int $earlyLine, array $declared, string $configName): HeadingViolation
    {
        if ($title === null) {
            $problem = sprintf('%s has no level-1 title, so it has no badge block below one', $path);
        } elseif ($earlyLine !== null) {
            $problem = sprintf('The badges of %s start on line %d, above the title "%s"', $path, $earlyLine, $title->notation());
        } else {
            $problem = sprintf('%s has no badge block directly below its title "%s"', $path, $title->notation());
        }

        return new HeadingViolation($path, $title === null ? $earlyLine : $title->line, 'missing_badges', $this->order($declared), null, sprintf(
            '%s. Place the badges on the lines after the "# " title, separated from it by one blank line, in the order %s declares: %s; changing the badges requires a human to update %s.',
            $problem,
            $configName,
            $this->order($declared),
            $configName,
        ));
    }

    /**
     * Reports a badge that is undeclared or out of the declared order.
     *
     * @param list<BadgeEntry> $declared
     */
    public function unexpectedBadge(string $path, Badge $badge, ?BadgeEntry $match, array $declared, string $configName): HeadingViolation
    {
        $problem = $match === null
            ? sprintf('Badge "%s" (%s) is not declared for %s in %s', $badge->label, $badge->image, $path, $configName)
            : sprintf('Badge "%s" is the declared "%s" badge but is out of order or repeated in %s', $badge->label, $match->name, $path);
        $fix = $match === null ? 'Remove the badge or replace it with a declared one' : 'Move the badge to its declared position and keep one of each';

        return new HeadingViolation($path, $badge->line, 'unexpected_badge', $this->order($declared), $badge->label, sprintf(
            '%s; %s declares the badge order %s. %s; allowing another badge requires a human to update %s.',
            $problem,
            $configName,
            $this->order($declared),
            $fix,
            $configName,
        ));
    }

    /**
     * Reports a required badge that the badge block lacks.
     *
     * @param string $position where the badge belongs, such as 'after the "PHP" badge'
     */
    public function missingBadge(string $path, BadgeEntry $badge, string $position, string $configName): HeadingViolation
    {
        return new HeadingViolation($path, null, 'missing_badge', $badge->name, null, sprintf(
            'Required badge "%s" is missing from the badge block of %s. Add a badge whose image URL matches "%s"%s %s; removing a required badge requires a human to update %s.',
            $badge->name,
            $path,
            $badge->image,
            $badge->label === null ? '' : sprintf(' with the text "%s"', $badge->label),
            $position,
            $configName,
        ));
    }

    /**
     * Reports a document whose content differs from the declared exact content.
     */
    public function unexpectedContent(string $path, string $expected, string $configName): HeadingViolation
    {
        $quoted = (string) json_encode($expected, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new HeadingViolation($path, null, 'unexpected_content', $expected, null, sprintf(
            '%s must contain exactly %s and nothing else, including no trailing newline, as declared in %s. Replace the whole file content with that text; changing the content requires a human to update %s.',
            $path,
            $quoted,
            $configName,
            $configName,
        ));
    }

    /**
     * Lists the declared badge names in order, marking optional and repeatable badges.
     *
     * @param list<BadgeEntry> $declared
     */
    public function order(array $declared): string
    {
        return implode(', ', array_map(static function (BadgeEntry $badge): string {
            $flags = [];
            if ($badge->optional) {
                $flags[] = 'optional';
            }
            if ($badge->repeat) {
                $flags[] = 'repeatable';
            }

            return $flags === [] ? $badge->name : $badge->name . ' (' . implode(', ', $flags) . ')';
        }, $declared));
    }

    /**
     * Names the other declared outlines when the document may follow more than one.
     *
     * @param list<string> $outlines
     */
    public function alternatives(array $outlines): string
    {
        return count($outlines) < 2 ? '' : sprintf(' (the closest of the declared outlines %s)', implode(', ', $outlines));
    }
}
