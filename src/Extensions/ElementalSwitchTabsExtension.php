<?php

namespace Sunnysideup\ElementalSwitchTabs\Extensions;

use SilverStripe\Forms\FieldList;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Controllers\ElementalAreaController;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Forms\DropdownField;

/**
 * Class \Sunnysideup\ElementalSwitchTabs\Extensions\ElementalSwitchTabsExtension
 *
 * @property BaseElement|ElementalSwitchTabsExtension $owner
 */
class ElementalSwitchTabsExtension extends Extension
{
    private static $show_change_type = true;

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $controller = Controller::curr();
        if (($controller && $controller instanceof ElementalAreaController)) {
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create(
                        'AllSettings',
                        '<a
                            href="' . $owner->MyCMSEditLink() . '"
                            style="float: right; display: block; width: auto;"
                        >Edit All Settings</a>'
                    ),
                ],
                'Title'
            );
        } elseif ($controller && ! ($controller instanceof CMSPageEditController)) {
            $page = $owner->getPage();
            $pageTitle = 'Page not found';
            if ($page) {
                $pageTitle = $page->MenuTitle;
            }
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create(
                        'AllSettings',
                        '<a
                            href="' . $owner->CMSEditLink(false) . '"
                            style="text-align: right; display: block; padding-bottom: 20px;"
                        >Edit on the "' . $pageTitle . '" page</a>'
                    ),
                ],
                'Title'
            );
        }
        if ($owner->Config()->get('show_change_type')) {
            $this->addChangeTypeField($fields);
        }
    }

    protected function addChangeTypeField(FieldList $fields)
    {
        $fields->addFieldsToTab(
            'Root.Settings',
            [
                DropdownField::create(
                    'ClassName',
                    'Change type of block',
                    $this->getClassDropdown()
                )
                    ->setDescription('Use with care! Changing the type of block can lead to loss of data for this block.'),
            ]
        );
    }



    protected function getClassDropdown(): array
    {
        $owner = $this->getOwner();
        $page = $owner->getPage();
        if ($page) {
            $list = $page->getElementalTypes();
            if (isset($list[$owner->ClassName])) {
                $list[$owner->ClassName] = $list[$owner->ClassName] . ' (current type)';
            } else {
                $list[$owner->ClassName] = $owner->singular_name() . ' (current type) - ERROR!';
            }
            return $list;
        }
        return [];
    }

    public function getLinksField(string $nameOfTab, string $label)
    {
        return LiteralField::create(
            'LinkToLink' . $nameOfTab,
            '<a href="#" onclick="' . $this->getJsFoTabSwitch($nameOfTab) . '">' . $label . '</a>'
        );
    }

    /**
     * @return BaseElement|null
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
        return null;
    }

    public function MyCMSEditLink(): string
    {
        $owner = $this->getOwner();
        return (string) $owner->CMSEditLink(true);
    }

    /**
     * @return BaseElement|null
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
        return null;
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
