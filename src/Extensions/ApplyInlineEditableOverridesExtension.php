<?php

declare(strict_types=1);

namespace Sunnysideup\ElementalSwitchTabs\Extensions;

use DNADesign\Elemental\Controllers\ElementalAreaController;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Flushable;

/**
 * Applied to LeftAndMain: switches off inline editing for block types whose
 * real CMS fields are not suitable, before Elemental builds its type list.
 */
class ApplyInlineEditableOverridesExtension extends Extension implements Flushable
{
    public function onBeforeInit(): void
    {
        // the inline form endpoint doesn't need it, and this avoids loops
        if ($this->getOwner() instanceof ElementalAreaController) {
            return;
        }

        ElementalSwitchTabsExtension::applyInlineEditableOverrides();
    }

    public static function flush(): void
    {
        ElementalSwitchTabsExtension::clearInlineEditableCache();
    }
}
