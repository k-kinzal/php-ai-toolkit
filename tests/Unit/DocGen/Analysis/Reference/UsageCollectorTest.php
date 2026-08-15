<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Reference\LocalTypeMap;
use PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use PhpAiToolkit\DocGen\Analysis\Reference\Usage;
use PhpAiToolkit\DocGen\Analysis\Reference\UsageCollector;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UsageCollector::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(LocalTypeMap::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PropertyTypeScanner::class)]
#[UsesClass(Usage::class)]
final class UsageCollectorTest extends TestCase
{
    public function testBeginFileAppliesFileAndDevFlagToRecordedUsages(): void
    {
        $collector = new UsageCollector();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $parser = new AstParser();
        $collector->beginFile('src/A.php', false);
        $traverser->traverse($parser->parse('<?php namespace Demo; new Alpha();', 'a.php'));
        $collector->beginFile('tests/BTest.php', true);
        $traverser->traverse($parser->parse('<?php namespace Demo; new Beta();', 'b.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('src/A.php', $usages[0]->file);
        self::assertFalse($usages[0]->fromDev);
        self::assertSame('tests/BTest.php', $usages[1]->file);
        self::assertTrue($usages[1]->fromDev);
    }

    public function testUsagesReturnsEmptyListBeforeTraversal(): void
    {
        self::assertSame([], (new UsageCollector())->usages());
    }

    public function testBeforeTraverseLeavesNodeListUnchanged(): void
    {
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $statements = (new AstParser())->parse('<?php new \Demo\Alpha();', 'a.php');

        self::assertSame($statements, $traverser->traverse($statements));
    }

    public function testAfterTraverseKeepsCollectedUsagesAvailable(): void
    {
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse('<?php new \Demo\Alpha();', 'a.php'));

        self::assertCount(1, $collector->usages());
    }

    public function testEnterNodeRecordsNewExpression(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): void
    {
        new Greeter();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\Greeter', $usages[0]->targetFqcn);
        self::assertNull($usages[0]->member);
        self::assertSame('new', $usages[0]->kind);
        self::assertSame('Demo\App', $usages[0]->fromFqcn);
        self::assertSame('run', $usages[0]->fromMember);
        self::assertSame(9, $usages[0]->line);
    }

    public function testEnterNodeRecordsInstanceofReference(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function check(object $value): bool
    {
        return $value instanceof Greeter;
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\Greeter', $usages[0]->targetFqcn);
        self::assertSame('instanceof', $usages[0]->kind);
        self::assertSame('check', $usages[0]->fromMember);
    }

    public function testEnterNodeRecordsAttributeReference(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

#[Marker]
class App
{
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\Marker', $usages[0]->targetFqcn);
        self::assertSame('attribute', $usages[0]->kind);
        self::assertSame('Demo\App', $usages[0]->fromFqcn);
        self::assertSame(5, $usages[0]->line);
    }

    public function testEnterNodeRecordsFunctionCallReference(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): void
    {
        \Demo\helper();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\helper', $usages[0]->targetFqcn);
        self::assertSame('function-call', $usages[0]->kind);
        self::assertSame('run', $usages[0]->fromMember);
    }

    public function testEnterNodeRecordsClassConstantAndClassNameFetch(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): string
    {
        $limit = Config::LIMIT;

        return Config::class;
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('Demo\Config', $usages[0]->targetFqcn);
        self::assertSame('LIMIT', $usages[0]->member);
        self::assertSame('class-const', $usages[0]->kind);
        self::assertSame(9, $usages[0]->line);
        self::assertSame('Demo\Config', $usages[1]->targetFqcn);
        self::assertSame('class', $usages[1]->member);
        self::assertSame('class-const', $usages[1]->kind);
        self::assertSame(11, $usages[1]->line);
    }

    public function testEnterExpressionDispatchesAllExpressionKinds(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(object $input): bool
    {
        $service = new Greeter();
        $service->greet();
        Registry::fetch();
        \Demo\helper();
        $limit = Config::LIMIT;

        return $input instanceof Contract;
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $kinds = array_map(static fn (Usage $usage): string => $usage->kind, $collector->usages());

        self::assertSame(['new', 'method-call', 'static-call', 'function-call', 'class-const', 'instanceof'], $kinds);
    }

    public function testVariableTypeReturnsNullOutsideAnyScope(): void
    {
        $collector = new UsageCollector();

        self::assertNull($collector->variableType('this'));
        self::assertNull($collector->variableType('service'));
    }

    public function testLeaveNodePopsClassScopeBeforeLaterCode(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class First
{
}

function helper(): void
{
    new Greeter();
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('new', $usages[0]->kind);
        self::assertNull($usages[0]->fromFqcn);
        self::assertSame('helper', $usages[0]->fromMember);
    }

    public function testEnterClassLikeRecordsExtendsImplementsAndTraitUse(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App extends Base implements Contract
{
    use Reusable;
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(3, $usages);
        self::assertSame('Demo\Base', $usages[0]->targetFqcn);
        self::assertSame('extends', $usages[0]->kind);
        self::assertSame('Demo\App', $usages[0]->fromFqcn);
        self::assertSame('Demo\Contract', $usages[1]->targetFqcn);
        self::assertSame('implements', $usages[1]->kind);
        self::assertSame('Demo\App', $usages[1]->fromFqcn);
        self::assertSame('Demo\Reusable', $usages[2]->targetFqcn);
        self::assertSame('use-trait', $usages[2]->kind);
        self::assertSame(7, $usages[2]->line);
    }

    public function testEnterClassLikeRecordsInterfaceAndEnumParents(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

interface Wide extends Narrow
{
}

enum Status: string implements Contract
{
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('Demo\Narrow', $usages[0]->targetFqcn);
        self::assertSame('extends', $usages[0]->kind);
        self::assertSame('Demo\Wide', $usages[0]->fromFqcn);
        self::assertSame('Demo\Contract', $usages[1]->targetFqcn);
        self::assertSame('implements', $usages[1]->kind);
        self::assertSame('Demo\Status', $usages[1]->fromFqcn);
    }

    public function testEnterFunctionLikeRecordsReturnTypeReference(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class Factory
{
    public function make(): ?Greeter
    {
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/Factory.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'factory.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\Greeter', $usages[0]->targetFqcn);
        self::assertSame('type', $usages[0]->kind);
        self::assertSame('Demo\Factory', $usages[0]->fromFqcn);
        self::assertNull($usages[0]->fromMember);
        self::assertSame(7, $usages[0]->line);
    }

    public function testEnterClosureInheritsVariableTypesFromEnclosingScope(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(Greeter $greeter): void
    {
        $callback = function () use ($greeter): void {
            $greeter->greet();
        };
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('type', $usages[0]->kind);
        self::assertSame('Demo\Greeter', $usages[1]->targetFqcn);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame('run', $usages[1]->fromMember);
        self::assertSame(10, $usages[1]->line);
    }

    public function testRegisterParamRecordsTypeAndSeedsMethodCallReceiver(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(Greeter $greeter): void
    {
        $greeter->greet();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('Demo\Greeter', $usages[0]->targetFqcn);
        self::assertSame('type', $usages[0]->kind);
        self::assertSame(7, $usages[0]->line);
        self::assertSame('Demo\Greeter', $usages[1]->targetFqcn);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame(9, $usages[1]->line);
    }

    public function testTrackAssignmentForgetsVariableOnUnknownReassignment(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): void
    {
        $service = new Greeter();
        $service->greet();
        $service = \Demo\make();
        $service->farewell();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(3, $usages);
        self::assertSame('new', $usages[0]->kind);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('function-call', $usages[2]->kind);
        self::assertSame('Demo\make', $usages[2]->targetFqcn);
    }

    public function testTrackAssignmentCopiesTypeFromKnownVariableAssignment(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(Greeter $greeter): void
    {
        $copy = $greeter;
        $copy->greet();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('Demo\Greeter', $usages[1]->targetFqcn);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame(10, $usages[1]->line);
    }

    public function testReceiverTypeResolvesTypedPropertyReceiver(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    private Greeter $greeter;

    public function run(): void
    {
        $this->greeter->greet();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('type', $usages[0]->kind);
        self::assertSame('Demo\Greeter', $usages[1]->targetFqcn);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame(11, $usages[1]->line);
    }

    public function testReceiverTypeResolvesPromotedConstructorPropertyReceiver(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function __construct(private Greeter $greeter)
    {
    }

    public function run(): void
    {
        $this->greeter->greet();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(2, $usages);
        self::assertSame('type', $usages[0]->kind);
        self::assertSame('Demo\Greeter', $usages[1]->targetFqcn);
        self::assertSame('greet', $usages[1]->member);
        self::assertSame('method-call', $usages[1]->kind);
        self::assertSame('run', $usages[1]->fromMember);
    }

    public function testRecordMethodCallResolvesThisReceiverToCurrentClass(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    public function run(): void
    {
        $this->step();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\App', $usages[0]->targetFqcn);
        self::assertSame('step', $usages[0]->member);
        self::assertSame('method-call', $usages[0]->kind);
        self::assertSame('Demo\App', $usages[0]->fromFqcn);
        self::assertSame('run', $usages[0]->fromMember);
    }

    public function testRecordStaticCallResolvesSelfStaticParentAndPlainNames(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App extends Base
{
    public function run(): void
    {
        self::alpha();
        static::beta();
        parent::gamma();
        Registry::delta();
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $usages = $collector->usages();

        self::assertCount(5, $usages);
        self::assertSame('extends', $usages[0]->kind);
        self::assertSame('Demo\App', $usages[1]->targetFqcn);
        self::assertSame('alpha', $usages[1]->member);
        self::assertSame('static-call', $usages[1]->kind);
        self::assertSame('Demo\App', $usages[2]->targetFqcn);
        self::assertSame('beta', $usages[2]->member);
        self::assertSame('Demo\Base', $usages[3]->targetFqcn);
        self::assertSame('gamma', $usages[3]->member);
        self::assertSame('Demo\Registry', $usages[4]->targetFqcn);
        self::assertSame('delta', $usages[4]->member);
    }

    public function testResolveClassRefReturnsNullForSpecialNamesOutsideClass(): void
    {
        $collector = new UsageCollector();

        self::assertNull($collector->resolveClassRef('self'));
        self::assertNull($collector->resolveClassRef('static'));
        self::assertNull($collector->resolveClassRef('parent'));
    }

    public function testResolveClassRefReturnsPlainNameUnchanged(): void
    {
        self::assertSame('Demo\Greeter', (new UsageCollector())->resolveClassRef('Demo\Greeter'));
    }

    public function testTypeNamesReturnsEmptyListForMissingType(): void
    {
        self::assertSame([], (new UsageCollector())->typeNames(null));
    }

    public function testTypeNamesExpandsUnionIntersectionAndNullableTypes(): void
    {
        $code = <<<'PHP'
<?php

namespace Demo;

class App
{
    private Alpha|Beta $either;

    private Gamma&Delta $both;

    public function pick(): ?Epsilon
    {
    }
}
PHP;
        $collector = new UsageCollector();
        $collector->beginFile('src/App.php', false);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($collector);
        $traverser->traverse((new AstParser())->parse($code, 'app.php'));

        $targets = array_map(static fn (Usage $usage): string => $usage->targetFqcn, $collector->usages());

        self::assertSame(['Demo\Alpha', 'Demo\Beta', 'Demo\Gamma', 'Demo\Delta', 'Demo\Epsilon'], $targets);
    }

    public function testRecordCapturesOriginAndDevFlagDirectly(): void
    {
        $collector = new UsageCollector();
        $collector->beginFile('tests/Unit/AppTest.php', true);
        $collector->record('Demo\Greeter', 'greet', 'method-call', 42);

        $usages = $collector->usages();

        self::assertCount(1, $usages);
        self::assertSame('Demo\Greeter', $usages[0]->targetFqcn);
        self::assertSame('greet', $usages[0]->member);
        self::assertSame('method-call', $usages[0]->kind);
        self::assertNull($usages[0]->fromFqcn);
        self::assertNull($usages[0]->fromMember);
        self::assertSame('tests/Unit/AppTest.php', $usages[0]->file);
        self::assertSame(42, $usages[0]->line);
        self::assertTrue($usages[0]->fromDev);
    }
}
