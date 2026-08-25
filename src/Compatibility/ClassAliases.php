<?php

declare(strict_types=1);

/**
 * Backward-compatible names for classes moved into responsibility namespaces.
 *
 * @var array<string, class-string>
 */
$classAliases = [
    'PhpAiToolkit\\PhpStan\\Rule\\ForbidBroadCatchRule' => PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ForbidBroadCatchRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\ForbidEmptyCatchRule' => PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ForbidEmptyCatchRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\ForbidGenericThrowsTagRule' => PhpAiToolkit\PhpStan\Rule\ExceptionHandling\ForbidGenericThrowsTagRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\RequireExceptionChainingRule' => PhpAiToolkit\PhpStan\Rule\ExceptionHandling\RequireExceptionChainingRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\RequireThrowsTagOnDirectThrowRule' => PhpAiToolkit\PhpStan\Rule\ExceptionHandling\RequireThrowsTagOnDirectThrowRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\ForbiddenMagicMethodCallRule' => PhpAiToolkit\PhpStan\Rule\ClassDesign\ForbiddenMagicMethodCallRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\NoNonPublicMethodRule' => PhpAiToolkit\PhpStan\Rule\ClassDesign\NoNonPublicMethodRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\OverrideMustHaveAttributeRule' => PhpAiToolkit\PhpStan\Rule\ClassDesign\OverrideMustHaveAttributeRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\ForbidClassLikeNameSuffixRule' => PhpAiToolkit\PhpStan\Rule\Naming\ForbidClassLikeNameSuffixRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\ForbiddenNamespaceRule' => PhpAiToolkit\PhpStan\Rule\Naming\ForbiddenNamespaceRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\RequireExhaustiveDispatchRule' => PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\RequireExhaustiveClassDispatchRule' => PhpAiToolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveClassDispatchRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\NoBrokenCodeExpectationRule' => PhpAiToolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\NoRedundantAssertInstanceOfRule' => PhpAiToolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpUnitMockApiRule' => PhpAiToolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiRule::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\MissingExampleErrorBuilder' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\MissingExampleErrorBuilder::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiClassPhpDocErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiConstantPhpDocErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiConstantPhpDocErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiExampleErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiExampleErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiMethodPhpDocErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPhpDocErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPhpDocErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPropertyPhpDocErrorCollector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiVisibilityDetector' => PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\Shared\\PathMarkerSplitter' => PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter::class,
    'PhpAiToolkit\\PhpStan\\Rule\\Shared\\RestrictedTestNamespaceMatcher' => PhpAiToolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher::class,
    'PhpAiToolkit\\PhpStan\\Rule\\Shared\\RulePathMatcher' => PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathMatcher::class,
    'PhpAiToolkit\\PhpStan\\Rule\\Shared\\RulePathNormalizer' => PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer::class,
    'PhpAiToolkit\\PhpStan\\Rule\\Shared\\SrcUnitTestRelativePathMapper' => PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\FilenameExclusionMatcher' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\SourceFileRuleMatcher' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileRuleMatcher::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\SourceFileUnitTestPairInspector' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\SourceUnitTestFileResolver' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairErrorBuilder' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairFileInspector' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector::class,
    'PhpAiToolkit\\PhpStan\\Rule\\TestClass\\UnitTestSourcePairInspector' => PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueAiFormatter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueBlockIndenter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueFormatter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueGutter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueHumanFormatter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssuePathFormatter' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueSummary' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary::class,
    'PhpAiToolkit\\PhpUnit\\TestReporter\\TestIssueTypePresentation' => PhpAiToolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\ClassLikeBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\ConstantBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\EnumCaseBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\FunctionBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\MethodBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\MethodBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\ParameterBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder::class,
    'PhpAiToolkit\\DocGen\\Analysis\\Parse\\PropertyBuilder' => PhpAiToolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder::class,
    'PhpAiToolkit\\DocGen\\Render\\SocialCard' => PhpAiToolkit\DocGen\Render\Social\SocialCard::class,
    'PhpAiToolkit\\DocGen\\Render\\SocialCardText' => PhpAiToolkit\DocGen\Render\Social\SocialCardText::class,
    'PhpAiToolkit\\DocGen\\Render\\SocialMeta' => PhpAiToolkit\DocGen\Render\Social\SocialMeta::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\BreadcrumbHtml' => PhpAiToolkit\DocGen\Render\Page\Component\BreadcrumbHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\DocTextHtml' => PhpAiToolkit\DocGen\Render\Page\Component\DocTextHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\DocumentListHtml' => PhpAiToolkit\DocGen\Render\Page\Component\DocumentListHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\ExampleHtml' => PhpAiToolkit\DocGen\Render\Page\Component\ExampleHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\GraphSvg' => PhpAiToolkit\DocGen\Render\Page\Component\GraphSvg::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\MemberHtml' => PhpAiToolkit\DocGen\Render\Page\Component\MemberHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\PrivateSurfaceHtml' => PhpAiToolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\RelationsHtml' => PhpAiToolkit\DocGen\Render\Page\Component\RelationsHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\SidebarHtml' => PhpAiToolkit\DocGen\Render\Page\Component\SidebarHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\SignatureHtml' => PhpAiToolkit\DocGen\Render\Page\Component\SignatureHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\SymbolDescription' => PhpAiToolkit\DocGen\Render\Page\Component\SymbolDescription::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\SymbolListHtml' => PhpAiToolkit\DocGen\Render\Page\Component\SymbolListHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\SymbolRow' => PhpAiToolkit\DocGen\Render\Page\Component\SymbolRow::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\TestCaseHtml' => PhpAiToolkit\DocGen\Render\Page\Component\TestCaseHtml::class,
    'PhpAiToolkit\\DocGen\\Render\\Page\\UsageListHtml' => PhpAiToolkit\DocGen\Render\Page\Component\UsageListHtml::class,
];

spl_autoload_register(static function (string $class) use ($classAliases): void {
    $currentClass = $classAliases[$class] ?? null;
    if ($currentClass === null) {
        return;
    }

    class_alias($currentClass, $class);
});
