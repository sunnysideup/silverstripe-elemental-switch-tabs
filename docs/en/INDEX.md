# tl;dr

Use like this:

```php

public function getCMSFields()
{
    $fields = parent::getCMSFields();
    $fields->addFieldToTab('Root.Main', $this->getLinksField('MyOtherTab', 'Please open MyOtherTab for more content editings'));
}
```
