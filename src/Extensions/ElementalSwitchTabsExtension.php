<?php

namespace Sunnysideup\ElementalSwitchTabs\Extensions;

use SilverStripe\Forms\FieldList;
use \Page;


class ElementalSwitchTabsExtension extends DataExtension
{

    public function getLinksField(string $nameOfTab, string $label)
    {
        return LiteralField::create(
            'LinkToLink'.$nameOfTab,
            '<a href="#" onclick="'.$this->getJsFoTabSwitch($nameOfTab).'">'.$label.'</a>'
        );
    }
    protected function getJsFoTabSwitch(string $nameOfTab) : string
    {
        $js = <<<js
        if(jQuery(this).closest('div.element-editor__element').length > 0) {
            jQuery(this)
                .closest('div.element-editor__element')
                .find('button[name=\'$nameOfTab\']')
                .click();
        } else {
            jQuery('li[aria-controls=\'Root_$nameOfTab\'] a').click();
        }
        return false;
js;
        return $js;
    }
}
