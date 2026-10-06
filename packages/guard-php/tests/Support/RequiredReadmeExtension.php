<?php

declare(strict_types=1);

namespace Tests\Support;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Extension\Extension;
use Guard\Extension\Registry;
use Guard\Reporting\Finding;

/** A metadata-only extension requiring a README, with no custom file collector. */
final class RequiredReadmeExtension implements Extension
{
    public function register(Registry $registry): void
    {
        $registry->addPolicy('readme', new CallbackPolicy(
            ['readme' => new Input(new Selection('files', ['README.md'], [], '', false, ''), null)],
            static function (InputSet $inputs, Context $context): Plan {
                $exists = $inputs->get('readme')->files['README.md']->file->entry->file;
                return new Plan($exists ? [] : [new Finding('README.md', 'readme.required', 'required', 'Create README.md.')], []);
            },
        ));
    }
}
