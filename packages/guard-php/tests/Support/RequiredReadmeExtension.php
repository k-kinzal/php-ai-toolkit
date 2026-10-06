<?php

declare(strict_types=1);

namespace Tests\Support;

use Guard\Collect\Subject;
use Guard\Collect\Tree\DirectoryTree;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Extension\Extension;
use Guard\Extension\Registry;
use Guard\Reporting\Finding;

final class RequiredReadmeExtension implements Extension
{
    public function register(Registry $registry): void
    {
        $registry->addPolicy('readme', DirectoryTree::class, new CallbackPolicy(static function (Subject $subject, Context $context): Plan {
            if (!$subject instanceof DirectoryTree || !isset($subject->listings['.'])) {
                return new Plan([], []);
            }
            $files = $subject->listings['.']->fileNames;
            return new Plan(in_array('README.md', $files, true) ? [] : [new Finding('README.md', 'readme.required', 'required', 'Add README.md to the project root.')], []);
        }));
    }
}
