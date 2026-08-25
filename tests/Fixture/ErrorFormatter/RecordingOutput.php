<?php

declare(strict_types=1);

namespace Tests\Fixture\ErrorFormatter;

use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;

final class RecordingOutput implements Output
{
    /** @var list<string> */
    private array $formattedLines = [];

    public function __construct(private OutputStyle $style)
    {
    }

    public function writeFormatted(string $message): void
    {
    }

    public function writeLineFormatted(string $message): void
    {
        $this->formattedLines[] = $message;
    }

    public function writeRaw(string $message): void
    {
    }

    public function getStyle(): OutputStyle
    {
        return $this->style;
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function isVeryVerbose(): bool
    {
        return false;
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function isDecorated(): bool
    {
        return false;
    }

    /** @return list<string> */
    public function formattedLines(): array
    {
        return $this->formattedLines;
    }
}
