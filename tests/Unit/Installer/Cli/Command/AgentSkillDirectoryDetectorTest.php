<?php

declare(strict_types=1);

namespace Tests\Unit\Installer\Cli\Command;

use function mkdir;

use PhpAiToolkit\Installer\Cli\Command\AgentSkillDirectoryDetector;
use PhpAiToolkit\Installer\Cli\Command\SkillFilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(AgentSkillDirectoryDetector::class)]
#[UsesClass(SkillFilesystemOperator::class)]
final class AgentSkillDirectoryDetectorTest extends TestCase
{
    public function testDetectReturnsDefaultClaudeWhenNoAgentsExist(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path, 0755, true);

        try {
            self::assertSame(['.claude' => '.claude/skills'], (new AgentSkillDirectoryDetector())->detect($path));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }

    public function testDetectReturnsDetectedAgentDirectories(): void
    {
        $path = sys_get_temp_dir() . '/php-ai-toolkit-test-' . uniqid();
        mkdir($path . '/.claude', 0755, true);
        mkdir($path . '/.agents', 0755, true);

        try {
            self::assertSame([
                '.claude' => '.claude/skills',
                '.agents' => '.agents/skills',
            ], (new AgentSkillDirectoryDetector())->detect($path));
        } finally {
            (new SkillFilesystemOperator())->remove($path);
        }
    }
}
