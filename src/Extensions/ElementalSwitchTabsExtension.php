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
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\Schema\FormSchema;

/**
 * Class \Sunnysideup\ElementalSwitchTabs\Extensions\ElementalSwitchTabsExtension
 *
 * @property BaseElement|ElementalSwitchTabsExtension $owner
 */
class ElementalSwitchTabsExtension extends Extension
{
    private static $show_change_type = true;

    private static $edit_svg = '
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
    role="img" aria-label="Edit all">
<title>Edit all</title>
<!-- Pencil -->
<path d="M16.5 3.5l4 4L9 19l-4 1 1-4 11.5-12.5z"/>
<!-- List lines -->
<line x1="4" y1="6" x2="10" y2="6"/>
<line x1="4" y1="12" x2="8" y2="12"/>
<line x1="4" y1="18" x2="8" y2="18"/>
</svg>
    ';

    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->getOwner();
        $controller = Controller::curr();
        $fields->addFieldsToTab(
            'Root.More…',
            [
                LiteralField::create(
                    'AllSettingsLinkInMore',
                    '
                        <p class="message warning">
                            There are more fields and settings available on the full-screen editing page.
                        </p>'
                ),
                LiteralField::create(
                    'AllSettingsLinkInMoreLink',
                    '
                        <p>
                            <a href="' . $owner->MyCMSEditLink() . '" class="btn action btn-outline-primary font-icon-edit-write" title="edit full-screen">Go to full-screen editing</a>
                        </p>'
                ),
            ],
        );
        if ($controller && $controller instanceof ElementalAreaController) {

            $fields->addFieldsToTab(
                'Root',
                [
                    LiteralField::create(
                        'AllSettingsLink',
                        '
                        <div class="edit-all-button">
                            <a href="' . $owner->MyCMSEditLink() . '">
                                <button
                                    type="button"
                                    aria-haspopup="false"
                                    aria-expanded="false"
                                    class="element-editor-header__actions-toggle btn btn-sm btn--no-text font-icon-edit-write btn btn-secondary" aria-label="Edit All"
                                >
                                    <span class="sr-only">View actions</span>
                                </button>
                            </a>
                        </div>'

                    ),
                ],
                'Title'
            );


            $callback = function (FieldList $fields) {
                $fieldsFlat = $fields->flattenFields();
                $hasMoreFields = false;
                foreach ($fieldsFlat as $tmpField) {
                    if (! $this->isReactReady($tmpField)) {
                        $fields->removeByName($tmpField->getName());
                        $hasMoreFields = true;
                    }
                }
                if (! $hasMoreFields) {
                    // $fields->fieldByName('AllSettingsLink')->setTitle('xxx');
                    $fields->fieldByName('Root.More….AllSettingsLinkInMore')
                        ->setValue(
                            ''

                        );
                    $fields->removeByName('AllSettingsLink');
                    // $fields->removeByName('Root.More….AllSettingsLinkInMore');
                    // $fields->removeByName('More…');
                    // $fields->removeByName('Root.More…');
                }
            };
            $this->callProtectedMethod($owner, 'afterUpdateCMSFields', [$callback]);
        } elseif ($controller && $controller instanceof CMSPageEditController) {
            $page = $owner->getPage();
            $pageTitle = 'Page not found';
            if ($page) {
                $pageTitle = $page->MenuTitle;
            }
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    LiteralField::create(
                        'AllSettingsLink',
                        '<div style="text-align: right"><a
                            href="' . $owner->CMSEditLink(false) . '"
                            class="btn action btn-secondary"
                            style=" "
                        >Edit on the "' . $pageTitle . '" page</a></div>'
                    ),
                ],
                'Title'
            );
            $fields->removeByName('AllSettingsLinkInMore');
            $fields->removeByName('AllSettingsLinkInMoreLink');
            $fields->removeByName('More…');
            $fields->removeByName('Root.More…');
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
const element = event.currentTarget
const elementEditor = element.closest('div.element-editor__element')

if (elementEditor) {
  const button = elementEditor.querySelector(`button[name='$nameOfTab']`)
  if (button) button.click()
} else {
  const tabLink = document.querySelector(`li[aria-controls='Root_$nameOfTab'] a`)
  if (tabLink) tabLink.click()
}

event.preventDefault()
return false
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
