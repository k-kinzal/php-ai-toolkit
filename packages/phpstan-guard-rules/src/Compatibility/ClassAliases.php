<?php

declare(strict_types=1);

if (!interface_exists(PHPStan\Analyser\IgnoreErrorExtension::class)) {
    class_alias(Toolkit\Compatibility\IgnoreErrorExtension::class, PHPStan\Analyser\IgnoreErrorExtension::class);
}

/**
 * Backward-compatible names for classes moved into responsibility namespaces.
 *
 * @var array<string, class-string>
 */
$classAliases = [
    'Toolkit\\PhpStan\\Rule\\ForbidBroadCatchRule' => Toolkit\PhpStan\Rule\ExceptionHandling\ForbidBroadCatchRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidEmptyCatchRule' => Toolkit\PhpStan\Rule\ExceptionHandling\ForbidEmptyCatchRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidGenericThrowsTagRule' => Toolkit\PhpStan\Rule\ExceptionHandling\ForbidGenericThrowsTagRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExceptionChainingRule' => Toolkit\PhpStan\Rule\ExceptionHandling\RequireExceptionChainingRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireThrowsTagOnDirectThrowRule' => Toolkit\PhpStan\Rule\ExceptionHandling\RequireThrowsTagOnDirectThrowRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbiddenMagicMethodCallRule' => Toolkit\PhpStan\Rule\ClassDesign\ForbiddenMagicMethodCallRule::class,
    'Toolkit\\PhpStan\\Rule\\NoNonPublicMethodRule' => Toolkit\PhpStan\Rule\ClassDesign\NoNonPublicMethodRule::class,
    'Toolkit\\PhpStan\\Rule\\OverrideMustHaveAttributeRule' => Toolkit\PhpStan\Rule\ClassDesign\OverrideMustHaveAttributeRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbidClassLikeNameSuffixRule' => Toolkit\PhpStan\Rule\Naming\ForbidClassLikeNameSuffixRule::class,
    'Toolkit\\PhpStan\\Rule\\ForbiddenNamespaceRule' => Toolkit\PhpStan\Rule\Naming\ForbiddenNamespaceRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExhaustiveDispatchRule' => Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveDispatchRule::class,
    'Toolkit\\PhpStan\\Rule\\RequireExhaustiveClassDispatchRule' => Toolkit\PhpStan\Rule\ControlFlow\RequireExhaustiveClassDispatchRule::class,
    'Toolkit\\PhpStan\\Rule\\NoBrokenCodeExpectationRule' => Toolkit\PhpStan\Rule\TestAssertion\NoBrokenCodeExpectationRule::class,
    'Toolkit\\PhpStan\\Rule\\NoRedundantAssertInstanceOfRule' => Toolkit\PhpStan\Rule\TestAssertion\NoRedundantAssertInstanceOfRule::class,
    'Toolkit\\PhpStan\\Rule\\PhpUnitMockApiRule' => Toolkit\PhpStan\Rule\TestAssertion\PhpUnitMockApiRule::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\MissingExampleErrorBuilder' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\MissingExampleErrorBuilder::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiClassPhpDocErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiConstantPhpDocErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiConstantPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiExampleErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiExampleErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiMethodPhpDocErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPhpDocErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiPropertyPhpDocErrorCollector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector::class,
    'Toolkit\\PhpStan\\Rule\\PhpDoc\\PublicApiVisibilityDetector' => Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\PathMarkerSplitter' => Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RestrictedTestNamespaceMatcher' => Toolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RulePathMatcher' => Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\RulePathNormalizer' => Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer::class,
    'Toolkit\\PhpStan\\Rule\\Shared\\SrcUnitTestRelativePathMapper' => Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\FilenameExclusionMatcher' => Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceFileRuleMatcher' => Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileRuleMatcher::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceFileUnitTestPairInspector' => Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SourceUnitTestFileResolver' => Toolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairErrorBuilder' => Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\SrcUnitTestPairFileInspector' => Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector::class,
    'Toolkit\\PhpStan\\Rule\\TestClass\\UnitTestSourcePairInspector' => Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueAiFormatter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueAiFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueBlockIndenter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueBlockIndenter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueFormatter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueGutter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueGutter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueHumanFormatter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueHumanFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssuePathFormatter' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssuePathFormatter::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueSummary' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueSummary::class,
    'Toolkit\\PhpUnit\\TestReporter\\TestIssueTypePresentation' => Toolkit\PhpUnit\TestReporter\Presentation\TestIssueTypePresentation::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ClassLikeBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\ClassLikeBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ConstantBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\ConstantBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\EnumCaseBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\EnumCaseBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\FunctionBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\FunctionBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\MethodBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\ParameterBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder::class,
    'Toolkit\\DocGen\\Analysis\\Parse\\PropertyBuilder' => Toolkit\DocGen\Analysis\Parse\Builder\PropertyBuilder::class,
    'Toolkit\\DocGen\\Render\\SocialCard' => Toolkit\DocGen\Render\Social\SocialCard::class,
    'Toolkit\\DocGen\\Render\\SocialCardText' => Toolkit\DocGen\Render\Social\SocialCardText::class,
    'Toolkit\\DocGen\\Render\\SocialMeta' => Toolkit\DocGen\Render\Social\SocialMeta::class,
    'Toolkit\\DocGen\\Render\\Page\\BreadcrumbHtml' => Toolkit\DocGen\Render\Page\Component\BreadcrumbHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\DocTextHtml' => Toolkit\DocGen\Render\Page\Component\DocTextHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\DocumentListHtml' => Toolkit\DocGen\Render\Page\Component\DocumentListHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\ExampleHtml' => Toolkit\DocGen\Render\Page\Component\ExampleHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\GraphSvg' => Toolkit\DocGen\Render\Page\Component\GraphSvg::class,
    'Toolkit\\DocGen\\Render\\Page\\MemberHtml' => Toolkit\DocGen\Render\Page\Component\MemberHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\PrivateSurfaceHtml' => Toolkit\DocGen\Render\Page\Component\PrivateSurfaceHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\RelationsHtml' => Toolkit\DocGen\Render\Page\Component\RelationsHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SidebarHtml' => Toolkit\DocGen\Render\Page\Component\SidebarHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SignatureHtml' => Toolkit\DocGen\Render\Page\Component\SignatureHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolDescription' => Toolkit\DocGen\Render\Page\Component\SymbolDescription::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolListHtml' => Toolkit\DocGen\Render\Page\Component\SymbolListHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\SymbolRow' => Toolkit\DocGen\Render\Page\Component\SymbolRow::class,
    'Toolkit\\DocGen\\Render\\Page\\TestCaseHtml' => Toolkit\DocGen\Render\Page\Component\TestCaseHtml::class,
    'Toolkit\\DocGen\\Render\\Page\\UsageListHtml' => Toolkit\DocGen\Render\Page\Component\UsageListHtml::class,
];

spl_autoload_register(static function (string $class) use ($classAliases): void {
    $currentClass = $classAliases[$class] ?? null;
    if ($currentClass === null) {
        return;
    }

    class_alias($currentClass, $class);
});
