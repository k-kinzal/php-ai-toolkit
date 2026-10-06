<?php

declare(strict_types=1);

namespace Guard\Collect\Markdown\Parsing;

use function count;
use function in_array;
use function preg_split;
use function rtrim;
use function str_starts_with;
use function substr;

/**
 * Splits Markdown source into lines and locates a leading YAML front matter block.
 */
final class MarkdownLineSplitter
{
    /**
     * Returns the source lines without line terminators or a UTF-8 byte order mark.
     *
     * @return list<string>
     */
    public function split(string $markdown): array
    {
        if (str_starts_with($markdown, "\xEF\xBB\xBF")) {
            $markdown = substr($markdown, 3);
        }

        $lines = preg_split('/\r\n|\n|\r/', $markdown);

        return $lines === false ? [$markdown] : $lines;
    }

    /**
     * Returns the number of leading lines that form a YAML front matter block, or 0 when there is none.
     *
     * @param list<string> $lines
     */
    public function frontMatterLength(array $lines): int
    {
        if ($lines === [] || rtrim($lines[0]) !== '---') {
            return 0;
        }

        $count = count($lines);
        for ($index = 1; $index < $count; $index++) {
            if (in_array(rtrim($lines[$index]), ['---', '...'], true)) {
                return $index + 1;
            }
        }

        return 0;
    }
}
