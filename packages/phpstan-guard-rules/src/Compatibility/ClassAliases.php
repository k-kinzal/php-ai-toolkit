<?php

declare(strict_types=1);
use PHPStan\Analyser\IgnoreErrorExtension;
use Toolkit\DocGen\Parse\Builder\ClassLikeBuilder;
use Toolkit\DocGen\Parse\Builder\ConstantBuilder;
use Toolkit\DocGen\Parse\Builder\EnumCaseBuilder;
use Toolkit\DocGen\Parse\Builder\FunctionBuilder;
use Toolkit\DocGen\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Parse\Builder\PropertyBuilder;
use Toolkit\DocGen\Report\Page\Component\BreadcrumbHtml;
use Toolkit\DocGen\Report\Page\Component\DocTextHtml;
use Toolkit\DocGen\Report\Page\Component\DocumentListHtml;
use Toolkit\DocGen\Report\Page\Component\ExampleHtml;
use Toolkit\DocGen\Report\Page\Component\GraphSvg;
use Toolkit\DocGen\Report\Page\Component\MemberHtml;
use Toolkit\DocGen\Report\Page\Component\PrivateSurfaceHtml;
use Toolkit\DocGen\Report\Page\Component\RelationsHtml;
use Toolkit\DocGen\Report\Page\Component\SidebarHtml;
use Toolkit\DocGen\Report\Page\Component\SignatureHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolDescription;
use Toolkit\DocGen\Report\Page\Component\SymbolListHtml;
use Toolkit\DocGen\Report\Page\Component\SymbolRow;
use Toolkit\DocGen\Report\Page\Component\TestCaseHtml;
use Toolkit\DocGen\Report\Page\Component\UsageListHtml;
use Toolkit\DocGen\Report\Social\SocialCard;
use Toolkit\DocGen\Report\Social\SocialCardText;
use Toolkit\DocGen\Report\Social\SocialMeta;
use Toolkit\PhpStan\Rule\ClassDesign\ForbiddenMagicMethodCallRule;
use Toolkit\PhpStan\Rule\ClassDesign\NoNonPublicMethodRule;
use Toolkit\PhpStan\Rule\ClassDesign\OverrideMustHaveAttributeRule;
use Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveClassDispatchRule;
use Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\ForbidBroadCatchRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\ForbidEmptyCatchRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\ForbidGenericThrowsTagRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\RequireExceptionChainingRule;
use Toolkit\PhpStan\Rule\ExceptionHandling\RequireThrowsTagOnDirectThrowRule;
use Toolkit\PhpStan\Rule\Naming\ForbidClassLikeNameSuffixRule;
use Toolkit\PhpStan\Rule\Naming\ForbiddenNamespaceRule;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\MissingExampleErrorBuilder;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiConstantPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiExampleErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;
use Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;
use Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule;
use Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfRule;
use Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiRule;
use Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileRuleMatcher;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector;
use Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary;
use Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation;

if (!interface_exists(IgnoreErrorExtension::class)) {
    class_alias(Toolkit\Compatibility\IgnoreErrorExtension::class, IgnoreErrorExtension::class);
}

/**
 * Backward-compatible names for classes moved into responsibility namespaces.
 *
 * @var array<string, class-string>
 */
$classAliases = [
    'Toolkit\\PhpStan\\Rule\\ForbidBroadCatchRule' => ForbidBroadCatchRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidEmptyCatchRule' => ForbidEmptyCatchRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidGenericThrowsTagRule' => ForbidGenericThrowsTagRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExceptionChainingRule' => RequireExceptionChainingRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireThrowsTagOnDirectThrowRule' => RequireThrowsTagOnDirectThrowRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbiddenMagicMethodCallRule' => ForbiddenMagicMethodCallRule::class,
    'Toolkit\\PhpStan\\Rule\\NoNonPublicMethodRule' => NoNonPublicMethodRule::class,
    'Toolkit\\PhpStan\\Rule\\OverrideMustHaveAttributeRule' => OverrideMustHaveAttributeRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidClassLikeNameSuffixRule' => ForbidClassLikeNameSuffixRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbiddenNamespaceRule' => ForbiddenNamespaceRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExhaustiveDispatchRule' => RequireExhaustiveDispatchRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExhaustiveClassDispatchRule' => RequireExhaustiveClassDispatchRule::class,
    'Toolkit\\PhpStan\\Rule\\NoBrokenCodeExpectationRule' => NoBrokenCodeExpectationRule::class,
    'Toolkit\\PhpStan\\Rule\\NoRedundantAssertInstanceOfRule' => NoRedundantAssertInstanceOfRule::class,
    'Toolkit\\PhpStan\\Rule\\PhpUnitMockApiRule' => PhpUnitMockApiRule::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\MissingExampleErrorBuilder' => MissingExampleErrorBuilder::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiClassPhpDocErrorCollector' => PublicApiClassPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiConstantPhpDocErrorCollector' => PublicApiConstantPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiExampleErrorCollector' => PublicApiExampleErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiMethodPhpDocErrorCollector' => PublicApiMethodPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPhpDocErrorCollector' => PublicApiPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPropertyPhpDocErrorCollector' => PublicApiPropertyPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiVisibilityDetector' => PublicApiVisibilityDetector::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\PathMarkerSplitter' => PathMarkerSplitter::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RestrictedTestNamespaceMatcher' => RestrictedTestNamespaceMatcher::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RulePathMatcher' => RulePathMatcher::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RulePathNormalizer' => RulePathNormalizer::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\SrcUnitTestRelativePathMapper' => SrcUnitTestRelativePathMapper::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\FilenameExclusionMatcher' => FilenameExclusionMatcher::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceFileRuleMatcher' => SourceFileRuleMatcher::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceFileUnitTestPairInspector' => SourceFileUnitTestPairInspector::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceUnitTestFileResolver' => SourceUnitTestFileResolver::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairErrorBuilder' => SrcUnitTestPairErrorBuilder::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairFileInspector' => SrcUnitTestPairFileInspector::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\UnitTestSourcePairInspector' => UnitTestSourcePairInspector::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueAiFormatter' => TestIssueAiFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueBlockIndenter' => TestIssueBlockIndenter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueFormatter' => TestIssueFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueGutter' => TestIssueGutter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueHumanFormatter' => TestIssueHumanFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssuePathFormatter' => TestIssuePathFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueSummary' => TestIssueSummary::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueTypePresentation' => TestIssueTypePresentation::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ClassLikeBuilder' => ClassLikeBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ConstantBuilder' => ConstantBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\EnumCaseBuilder' => EnumCaseBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\FunctionBuilder' => FunctionBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\MethodBuilder' => MethodBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ParameterBuilder' => ParameterBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\PropertyBuilder' => PropertyBuilder::class,
    'Toolkit\\DocGen\\Render\\SocialCard' => SocialCard::class,
    'Toolkit\\DocGen\\Render\\SocialCardText' => SocialCardText::class,
    'Toolkit\\DocGen\\Render\\SocialMeta' => SocialMeta::class,
    'Toolkit\\DocGen\\Render\\Page\\BreadcrumbHtml' => BreadcrumbHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\DocTextHtml' => DocTextHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\DocumentListHtml' => DocumentListHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\ExampleHtml' => ExampleHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\GraphSvg' => GraphSvg::class,
    'Toolkit\\DocGen\\Render\\Page\\MemberHtml' => MemberHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\PrivateSurfaceHtml' => PrivateSurfaceHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\RelationsHtml' => RelationsHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SidebarHtml' => SidebarHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SignatureHtml' => SignatureHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolDescription' => SymbolDescription::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolListHtml' => SymbolListHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolRow' => SymbolRow::class,
    'Toolkit\\DocGen\\Render\\Page\\TestCaseHtml' => TestCaseHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\UsageListHtml' => UsageListHtml::class,
];

spl_autoload_register(static function (string $class) use ($classAliases): void {
    $currentClass = $classAliases[$class] ?? null;
    if ($currentClass === null) {
        return;
    }

    class_alias($currentClass, $class);
});
