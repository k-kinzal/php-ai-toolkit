<?php

declare(strict_types=1);

namespace PhpAiToolkit\Installer\Cli\Command;

use function is_dir;

/**
 * Detects AI agent skill directories in a project root.
 */
final class AgentSkillDirectoryDetector
{
    /**
     * Known agent skill directory mappings.
     *
     * @var array<string, string>
     */
    private const AGENT_SKILL_DIRS = [
        '.claude' => '.claude/skills',
        '.agents' => '.agents/skills',
        '.continue' => '.continue/skills',
        '.openhands' => '.openhands/skills',
        '.windsurf' => '.windsurf/skills',
        '.factory' => '.factory/skills',
    ];

    /**
     * Returns detected agent skill directories, defaulting to Claude when none exist.
     *
     * @return array<string, string> map of agent name to skills directory path
     */
    public function detect(string $projectRoot): array
    {
        $detected = [];

        foreach (self::AGENT_SKILL_DIRS as $marker => $skillsDir) {
            if (is_dir($projectRoot . '/' . $marker)) {
                $detected[$marker] = $skillsDir;
            }
        }

        if ($detected === []) {
            return ['.claude' => '.claude/skills'];
        }

        return $detected;
    }
}
