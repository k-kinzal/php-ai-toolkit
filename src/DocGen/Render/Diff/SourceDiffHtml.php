<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render\Diff;

use function sprintf;

use Toolkit\DocGen\Analysis\Diff\DiffLine;
use Toolkit\DocGen\Analysis\Diff\LineDiffer;
use Toolkit\DocGen\Render\RenderKit;

/**
 * Renders one source file as the merge of its two revisions.
 *
 * The line numbering follows the head revision so every source link of the
 * site keeps working; a line the head no longer has carries the number it
 * had in the base revision and no anchor.
 */
final class SourceDiffHtml
{
    /** @readonly */
    private LineDiffer $differ;

    /**
     * Creates a source diff renderer from its line differ.
     */
    public function __construct(?LineDiffer $differ = null)
    {
        $this->differ = $differ ?? new LineDiffer();
    }

    /**
     * Renders the merged line listing of one file.
     *
     * @param ?string $base the file as the base revision had it
     * @param ?string $head the file as the head revision has it
     */
    public function listing(RenderKit $services, ?string $base, ?string $head): string
    {
        $highlighter = $services->highlighter;
        $lines = $this->differ->merge(
            $base === null ? [] : $this->differ->lines($base),
            $head === null ? [] : $this->differ->lines($head),
            $base === null ? [] : $this->differ->lines($highlighter->highlight($base)),
            $head === null ? [] : $this->differ->lines($highlighter->highlight($head)),
        );
        $html = '';
        foreach ($lines as $line) {
            $html .= $this->line($services, $line);
        }

        return $html;
    }

    /**
     * Renders one line of the merged listing.
     */
    public function line(RenderKit $services, DiffLine $line): string
    {
        $number = $line->headNumber;

        return sprintf(
            '<span class="src-line"%s%s>%s%s</span>' . "\n",
            $number !== null ? sprintf(' id="L%d"', $number) : '',
            $services->diff->mark($line->status),
            $number !== null
                ? sprintf('<a class="ln" href="#L%d">%d</a>', $number, $number)
                : sprintf('<span class="ln">%d</span>', $line->baseNumber ?? 0),
            $line->text,
        );
    }
}
