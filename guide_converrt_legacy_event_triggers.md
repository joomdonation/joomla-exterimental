# Legacy `triggerEvent` Conversion Guide
This guide documents how to convert deprecated `$app->triggerEvent()` calls to the modern `$dispatcher->dispatch()` pattern using typed event classes, in preparation for Joomla 7.0 where the legacy API will be removed.
## General Conversion Pattern
### Before (deprecated)
```php
Factory::getApplication()->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$item->params, 0]);
$results = Factory::getApplication()->triggerEvent('onContentAfterTitle', ['com_content.article', &$item, &$item->params, 0]);
```
### After (modern)
```php
use Joomla\CMS\Event\Content;
$dispatcher = $this->getDispatcher(); // or Factory::getApplication()->getDispatcher() in layout files
$dispatcher->dispatch('onContentPrepare', new Content\ContentPrepareEvent('onContentPrepare', [
    'context' => 'com_content.article',
    'subject' => $item,
    'params'  => $item->params,
    'page'    => 0,
]));
$results = $dispatcher->dispatch('onContentAfterTitle', new Content\AfterTitleEvent('onContentAfterTitle', [
    'context' => 'com_content.article',
    'subject' => $item,
    'params'  => $item->params,
    'page'    => 0,
]))->getArgument('result', []);
```
## Steps to Convert a Component
1. **Identify all `triggerEvent` calls** in the component using `grep -r "triggerEvent" .`
2. **Find the event class** for each event name (see the mapping table below).
3. **Get the dispatcher**: 
   - In MVC classes (controllers, models, views): `$dispatcher = $this->getDispatcher();`
   - In layout/template files: `$dispatcher = Factory::getApplication()->getDispatcher();`
4. **Update existing `PluginHelper::importPlugin()` calls** to pass `$dispatcher` if not already done:
   ```php
   // Before
   PluginHelper::importPlugin('content');
   // After
   PluginHelper::importPlugin('content', null, true, $dispatcher);
   ```
   > **Important**: Only update existing `PluginHelper::importPlugin()` calls to add `$dispatcher`. Do **not** add new `PluginHelper::importPlugin()` calls where none existed before.
5. **Replace each `triggerEvent` call** with `$dispatcher->dispatch()` using the appropriate typed event class.
6. **For events that return results** (e.g., `onContentAfterTitle`, `onContentBeforeDisplay`, `onContentAfterDisplay`), retrieve results with `.getArgument('result', [])` on the dispatched event.
## Event Name → Class Mapping
### Content Events (`use Joomla\CMS\Event\Content`)
| Event name                 | Class                              |
|----------------------------|------------------------------------|
| `onContentPrepare`         | `Content\ContentPrepareEvent`      |
| `onContentAfterTitle`      | `Content\AfterTitleEvent`          |
| `onContentBeforeDisplay`   | `Content\BeforeDisplayEvent`       |
| `onContentAfterDisplay`    | `Content\AfterDisplayEvent`        |
### Model Events (`use Joomla\CMS\Event\Model as ModelEvent`)
| Event name             | Class                          |
|------------------------|--------------------------------|
| `onContentAfterSave`   | `ModelEvent\AfterSaveEvent`    |
| `onContentBeforeSave`  | `ModelEvent\BeforeSaveEvent`   |
| `onContentAfterDelete` | `ModelEvent\AfterDeleteEvent`  |
## Content Event Arguments
For most content events (`onContentPrepare`, `onContentAfterTitle`, `onContentBeforeDisplay`, `onContentAfterDisplay`):
```php
[
    'context' => 'com_content.article',  // component.view context string
    'subject' => $item,                  // the content item (object)
    'params'  => $item->params,          // item or view params
    'page'    => 0,                      // page offset (usually 0)
]
```
For save/delete model events (`onContentAfterSave`, `onContentBeforeSave`):
```php
[
    'context' => 'com_content.article',  // component.view context string
    'subject' => $this->table,           // the table object
    'isNew'   => false,                  // whether this is a new record
    'data'    => $data,                  // form data array
]
```
## Full Example: Converting a View
```php
// In a HtmlView.php
use Joomla\CMS\Event\Content;
use Joomla\CMS\Plugin\PluginHelper;
// In display() or prepare() method:
$dispatcher = $this->getDispatcher();
PluginHelper::importPlugin('content', null, true, $dispatcher);
foreach ($this->items as $item) {
    $item->slug = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
    // Convert: $app->triggerEvent('onContentPrepare', ['com_content.article', &$item, &$item->params, 0]);
    $dispatcher->dispatch('onContentPrepare', new Content\ContentPrepareEvent('onContentPrepare', [
        'context' => 'com_content.article',
        'subject' => $item,
        'params'  => $item->params,
        'page'    => 0,
    ]));
    // Convert: $results = $app->triggerEvent('onContentAfterTitle', [...]);
    $item->event->afterDisplayTitle = trim(implode("\n",
        $dispatcher->dispatch('onContentAfterTitle', new Content\AfterTitleEvent('onContentAfterTitle', [
            'context' => 'com_content.article',
            'subject' => $item,
            'params'  => $item->params,
            'page'    => 0,
        ]))->getArgument('result', [])
    ));
    // Convert: $results = $app->triggerEvent('onContentBeforeDisplay', [...]);
    $item->event->beforeDisplayContent = trim(implode("\n",
        $dispatcher->dispatch('onContentBeforeDisplay', new Content\BeforeDisplayEvent('onContentBeforeDisplay', [
            'context' => 'com_content.article',
            'subject' => $item,
            'params'  => $item->params,
            'page'    => 0,
        ]))->getArgument('result', [])
    ));
    // Convert: $results = $app->triggerEvent('onContentAfterDisplay', [...]);
    $item->event->afterDisplayContent = trim(implode("\n",
        $dispatcher->dispatch('onContentAfterDisplay', new Content\AfterDisplayEvent('onContentAfterDisplay', [
            'context' => 'com_content.article',
            'subject' => $item,
            'params'  => $item->params,
            'page'    => 0,
        ]))->getArgument('result', [])
    ));
}
```
## Applicable Components
The same conversion pattern applies to:
- `com_contact`
- `com_users`
- `com_newsfeeds`
- `com_weblinks`
- Any extension that uses content plugin events
Simply replace the `context` string with the appropriate component context (e.g., `com_contact.contact`).