<?php

namespace Sunnysideup\ElementalSwitchTabs\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataExtension;

class ElementalSwitchTabsExtension extends DataExtension
{
    public function getLinksField(string $nameOfTab, string $label)
    {
        return LiteralField::create(
            'LinkToLink' . $nameOfTab,
            '<a href="#" onclick="' . $this->getJsFoTabSwitch($nameOfTab) . '">' . $label . '</a>'
        );
    }

    /**
     * @return null|BaseElement
     */
    public function PreviousBlock()
    {
        $owner = $this->getOwner();
        if ($owner->exists()) {
            $parent = $owner->Parent();
            if ($parent) {
                return $parent->Elements()
                    ->filter(['Sort:LessThanOrEqual' => $owner->Sort])
                    ->exclude(['ID' => $owner->ID])
                    ->sort(['Sort' => 'ASC'])
                    ->last()
                ;
            }
        }
    }

    public function MyCMSEditLink(): string
    {
        $owner = $this->getOwner();
        $page = $owner->getPage();

        return '/admin/pages/edit/EditForm/' . ($page ? $page->ID : 0) . '/field/ElementalArea/item/' . ($owner ? $owner->ID : 0) . '/edit';
    }

    /**
     * @return null|BaseElement
     */
    public function NextBlock()
    {
        $owner = $this->getOwner();
        if ($owner->exists()) {
            $parent = $owner->Parent();
            if ($parent) {
                return $parent->Elements()
                    ->filter(['Sort:GreaterThanOrEqual' => $owner->Sort])
                    ->exclude(['ID' => $owner->ID])
                    ->sort(['Sort' => 'ASC'])
                    ->first()
                ;
            }
        }
    }

    protected function getJsFoTabSwitch(string $nameOfTab): string
    {
        return <<<js
        if(jQuery(this).closest('div.element-editor__element').length > 0) {
            jQuery(this)
                .closest('div.element-editor__element')
                .find('button[name=\\'{$nameOfTab}\\']')
                .click();
        } else {
            jQuery('li[aria-controls=\\'Root_{$nameOfTab}\\'] a').click();
        }
        return false;
js;
    }
}
