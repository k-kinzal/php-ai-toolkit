<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Analysis\ApplyRuleMatcher;
use Toolkit\LocGuard\Analysis\FilePolicyAssigner;
use Toolkit\LocGuard\Analysis\FilePolicyAssignment;
use Toolkit\LocGuard\Cli\LocGuardConfigPathResolver;
use Toolkit\LocGuard\Cli\LocGuardExplainRunner;
use Toolkit\LocGuard\Cli\LocGuardOutputWriter;
use Toolkit\LocGuard\Cli\PolicyExplanationFormatter;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigLoader;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\ConfigStringListReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\LocGuardConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfig;
use Toolkit\LocGuard\Config\Policy\ApplyConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyPolicyUsageValidator;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfig;
use Toolkit\LocGuard\Config\Policy\ApplyRuleConfigReader;
use Toolkit\LocGuard\Config\Policy\ApplyRuleListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyResolver;
use Toolkit\LocGuard\Config\ReportConfig;
use Toolkit\LocGuard\Config\ReportConfigReader;
use Toolkit\LocGuard\Config\ScanConfig;
use Toolkit\LocGuard\Config\ScanConfigReader;
use Toolkit\LocGuard\Filesystem\FilePathPatternMatcher;
use Toolkit\LocGuard\Filesystem\LocGuardPathResolver;
use Toolkit\LocGuard\Filesystem\PhpFileFinder;
use Toolkit\LocGuard\Filesystem\PhpFileInclusionPolicy;
use Toolkit\LocGuard\Filesystem\PhpPathFileCollector;

#[CoversClass(LocGuardExplainRunner::class)]
#[UsesClass(ApplyRuleMatcher::class)]
#[UsesClass(FilePolicyAssigner::class)]
#[UsesClass(FilePolicyAssignment::class)]
#[UsesClass(LocGuardConfigPathResolver::class)]
#[UsesClass(LocGuardOutputWriter::class)]
#[UsesClass(PolicyExplanationFormatter::class)]
#[UsesClass(ApplyConfig::class)]
#[UsesClass(ApplyConfigReader::class)]
#[UsesClass(ApplyPolicyUsageValidator::class)]
#[UsesClass(ApplyRuleConfig::class)]
#[UsesClass(ApplyRuleConfigReader::class)]
#[UsesClass(ApplyRuleListConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigLoader::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(ConfigStringListReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(LocGuardConfig::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyListConfigReader::class)]
#[UsesClass(PolicyResolver::class)]
#[UsesClass(ReportConfig::class)]
#[UsesClass(ReportConfigReader::class)]
#[UsesClass(ScanConfig::class)]
#[UsesClass(ScanConfigReader::class)]
#[UsesClass(FilePathPatternMatcher::class)]
#[UsesClass(LocGuardPathResolver::class)]
#[UsesClass(PhpFileFinder::class)]
#[UsesClass(PhpFileInclusionPolicy::class)]
#[UsesClass(PhpPathFileCollector::class)]
final class LocGuardExplainRunnerTest extends TestCase
{
    public function testRunPrintsEffectivePolicyForScannedFile(): void
    {
        $dir = sys_get_temp_dir() . '/locguard-explain-' . uniqid('', true);
        mkdir($dir . '/src', 0755, true);
        file_put_contents($dir . '/src/Native.php', "<?php\n");
        file_put_contents($dir . '/loc.yaml', <<<'YAML'
scan:
  roots: [src]
policies:
  standard:
    limits:
      file: { lines: 500 }
  native-api:
    extends: standard
    limits:
      file: { lines: 900 }
apply:
  default: standard
  rules:
    - name: native
      match:
        paths: ['src/Native.php']
      policy: native-api
YAML);
        $output = '';
        $runner = new LocGuardExplainRunner(
            $dir,
            new ConfigLoader(),
            new LocGuardOutputWriter(stdout: static function (string $message) use (&$output): void {
                $output .= $message;
            }),
        );

        self::assertSame(0, $runner->run('loc.yaml', 'src/Native.php'));
        self::assertStringContainsString('Policy: native-api', $output);
        self::assertStringContainsString('file.lines: 900', $output);
    }
}
