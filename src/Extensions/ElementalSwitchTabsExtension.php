<?php

namespace Sunnysideup\ElementalSwitchTabs\Extensions;

use SilverStripe\Forms\FieldList;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Controllers\ElementalAreaController;
use ReflectionClass;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\Schema\FormSchema;

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
                            target="_all_settings"
                        >Edit All Settings</a>'
                    ),
                ],
                'Title'
            );
            $callback = function (FieldList $fields) {
                $fieldsFlat = $fields->flattenFields();
                foreach ($fieldsFlat as $field) {
                    if (! $this->isReactReady($field)) {
                        $fields->removeByName($field->getName());
                    }
                }
            };
            $this->callProtectedMethod($owner, 'afterUpdateCMSFields', [$callback]);
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


    protected static array $reactReadyCache = [];

    protected function isReactReady(FormField $field): bool
    {
        $className = $field::class;

        if ($className === GridField::class) {
            return false;
        }
        if ($field->getSchemaDataType()) {
            return true;
        }

        return self::$reactReadyCache[$className]
            ??= $this->hasSchemaMethodsIndicatingReact($className);
    }

    private function callProtectedMethod(object $object, string $methodName, array $args = []): mixed
    {
        $ref = new ReflectionClass($object);
        $method = $ref->getMethod($methodName);
        $method->setAccessible(true); // temporarily override visibility
        return $method->invokeArgs($object, $args);
    }

    private function hasSchemaMethodsIndicatingReact(string $className): bool
    {
        $ref = new ReflectionClass($className);
        foreach (['getSchemaStateDefaults', 'getSchemaDataDefaults'] as $methodName) {
            if (! $ref->hasMethod($methodName)) {
                continue;
            }
            $m = $ref->getMethod($methodName);

            // React-ready if the method is implemented by this class OR any subclass of FormField (not base)
            $decl = $m->getDeclaringClass()->getName();
            if ($decl === $className) {
                return true; // defined exactly here
            }
            if ($decl !== FormField::class) {
                return true; // overridden upstream (still React schema-capable)
            }
        }
        return false;
    }
}
