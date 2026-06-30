<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Administrator\Controller;

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Menu ItemModel  Controller
 *
 * @since  1.6
 */
class ItemController extends FormController
{
    /**
     * Method to check if you can add a new record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  boolean
     *
     * @since   3.6
     */
    protected function allowAdd($data = [])
    {
        $user = $this->app->getIdentity();

        $menuType = $this->input->getCmd('menutype', $data['menutype'] ?? '');

        $menutypeID = 0;

        // Load menutype ID
        if ($menuType) {
            $menutypeID = (int) $this->getMenuTypeId($menuType);
        }

        return $user->authorise('core.create', 'com_menus.menu.' . $menutypeID);
    }

    /**
     * Method to check if you edit a record.
     *
     * Extended classes can override this if necessary.
     *
     * @param   array   $data  An array of input data.
     * @param   string  $key   The name of the key for the primary key; default is id.
     *
     * @return  boolean
     *
     * @since   3.6
     */
    protected function allowEdit($data = [], $key = 'id')
    {
        $user = $this->app->getIdentity();

        $menutypeID = 0;

        if (isset($data[$key])) {
            $model = $this->getModel();
            $item  = $model->getItem($data[$key]);

            if (!empty($item->menutype)) {
                // Protected menutype, do not allow edit
                if ($item->menutype == 'main') {
                    return false;
                }

                $menutypeID = (int) $this->getMenuTypeId($item->menutype);
            }
        }

        return $user->authorise('core.edit', 'com_menus.menu.' . (int) $menutypeID);
    }

    /**
     * Loads the menutype ID by a given menutype string
     *
     * @param   string  $menutype  The given menutype
     *
     * @return integer
     *
     * @since  3.6
     */
    protected function getMenuTypeId($menutype)
    {
        $model = $this->getModel();
        $table = $model->getTable('MenuType');

        $table->load(['menutype' => $menutype]);

        return (int) $table->id;
    }

    /**
     * Method to add a new menu item.
     *
     * @return  mixed  True if the record can be added, otherwise false.
     *
     * @since   1.6
     */
    public function add()
    {
        $result = parent::add();

        if ($result) {
            $context = 'com_menus.edit.item';

            $this->app->setUserState($context . '.type', null);
            $this->app->setUserState($context . '.link', null);
        }

        return $result;
    }

    /**
     * Method to run batch operations.
     *
     * @param   object  $model  The model.
     *
     * @return  boolean  True if successful, false otherwise and internal error is set.
     *
     * @since   1.6
     */
    public function batch($model = null)
    {
        $this->checkToken();

        /** @var \Joomla\Component\Menus\Administrator\Model\ItemModel $model */
        $model = $this->getModel('Item', 'Administrator', []);

        // Preset the redirect
        $this->setRedirect(Route::_('index.php?option=com_menus&view=items' . $this->getRedirectToListAppend(), false));

        return parent::batch($model);
    }

    /**
     * Method to cancel an edit.
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     *
     * @since   1.6
     */
    public function cancel($key = null)
    {
        $this->checkToken();

        $result = parent::cancel();

        if ($result) {
            // Clear the ancillary data from the session.
            $context = 'com_menus.edit.item';
            $this->app->setUserState($context . '.type', null);
            $this->app->setUserState($context . '.link', null);


            // When editing in modal then redirect to modalreturn layout
            if ($this->input->get('layout') === 'modal') {
                $id     = $this->input->get('id');
                $return = 'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($id)
                    . '&layout=modalreturn&from-task=cancel';
            } else {
                $return = 'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend()
                    . '&menutype=' . $this->app->getUserState('com_menus.items.menutype');
            }

            // Redirect to the list screen.
            $this->setRedirect(Route::_($return, false));
        }

        return $result;
    }

    /**
     * Method to edit an existing record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key
     * (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if access level check and checkout passes, false otherwise.
     *
     * @since   1.6
     */
    public function edit($key = null, $urlVar = null)
    {
        $result = parent::edit();

        if ($result) {
            // Push the new ancillary data into the session.
            $this->app->setUserState('com_menus.edit.item.type', null);
            $this->app->setUserState('com_menus.edit.item.link', null);
        }

        return $result;
    }

    /**
     * Gets the URL arguments to append to an item redirect.
     *
     * @param   integer  $recordId  The primary key id for the item.
     * @param   string   $urlVar    The name of the URL variable for the id.
     *
     * @return  string  The arguments to append to the redirect URL.
     *
     * @since   3.0.1
     */
    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);

        if ($recordId) {
            /** @var \Joomla\Component\Menus\Administrator\Model\ItemModel $model */
            $model    = $this->getModel();
            $item     = $model->getItem($recordId);
            $clientId = $item->client_id;
            $append   = '&client_id=' . $clientId . $append;
        } else {
            $clientId = $this->input->get('client_id', '0', 'int');
            $menuType = $this->input->get('menutype', 'mainmenu', 'cmd');
            $append   = '&client_id=' . $clientId . ($menuType ? '&menutype=' . $menuType : '') . $append;
        }

        return $append;
    }

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   1.6
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');

        // Set the menutype should we need it.
        if ($data['menutype'] !== '') {
            $this->input->set('menutype', $data['menutype']);
        }

        // URL validation and protocol check for url type menu items.
        if ($data['type'] == 'url') {
            $data['link'] = str_replace(['"', '>', '<'], '', $data['link']);

            if (strstr($data['link'], ':')) {
                $segments = explode(':', $data['link']);
                $protocol = strtolower($segments[0]);
                $scheme   = [
                    'http', 'https', 'ftp', 'ftps', 'gopher', 'mailto',
                    'news', 'prospero', 'telnet', 'rlogin', 'tn3270', 'wais',
                    'mid', 'cid', 'nntp', 'tel', 'urn', 'ldap', 'file', 'fax',
                    'modem', 'git', 'sms',
                ];

                if (!\in_array($protocol, $scheme)) {
                    $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'warning');
                    $this->setRedirect(
                        Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($this->input->getInt('id')), false)
                    );

                    return false;
                }
            }
        }

        // Call parent save method to handle the rest.
        return parent::save($key, $urlVar);
    }

    /**
     * Method to preprocess data gotten from the request before further processing.
     *
     * @param   array  $data  The data array.
     *
     * @return  array  The processed data array.
     *
     * @since   6.1.0
     */
    protected function preprocessSaveData(array $data): array
    {
        // For component type menu items, we need to process the request params.
        // This is done here because the link field needs to be built from the request params
        // before the standard validation runs in parent save().
        if ($data['type'] == 'component') {
            /** @var \Joomla\Component\Menus\Administrator\Model\ItemModel $model */
            $model = $this->getModel('Item', 'Administrator', []);
            $form  = $model->getForm($data);

            if (!$form) {
                throw new \Exception($model->getError(), 500);
            }

            $request = $form->getGroup('request', true);

            if (!empty($request)) {
                $removeArgs = [];

                if (!isset($data['request']) || !\is_array($data['request'])) {
                    $data['request'] = [];
                }

                // Determine which request params should be removed (empty ones).
                foreach ($request as $field) {
                    $fieldName = $field->getAttribute('name');

                    if (!isset($data['request'][$fieldName]) || $data['request'][$fieldName] == '') {
                        $removeArgs[$fieldName] = '';
                    }
                }

                // Parse the submitted link arguments.
                $args = [];
                parse_str(parse_url($data['link'], PHP_URL_QUERY), $args);

                // Merge in the user supplied request arguments.
                $args = array_merge($args, $data['request']);

                // Remove the unused request params.
                if (!empty($args) && !empty($removeArgs)) {
                    $args = array_diff_key($args, $removeArgs);
                }

                // Rebuild the link with the cleaned params.
                $data['link'] = 'index.php?' . urldecode(http_build_query($args, '', '&'));
            }
        }

        return $data;
    }

    /**
     * Function that allows child controller access to model data after the data has been saved.
     *
     * @param   \Joomla\CMS\MVC\Model\BaseDatabaseModel  $model      The data model object.
     * @param   array                                    $validData  The validated data.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function postSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, $validData = [])
    {
        $task    = $this->getTask();
        $context = 'com_menus.edit.item';

        // Clear ancillary session data.
        $this->app->setUserState($context . '.type', null);
        $this->app->setUserState($context . '.link', null);

        // When editing in modal then redirect to modalreturn layout.
        if ($this->input->get('layout') === 'modal' && $task === 'save') {
            $recordId = $model->getState($this->context . '.id');
            $return   = 'index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId)
                . '&layout=modalreturn&from-task=save';

            $this->setRedirect(Route::_($return, false));
        } elseif ($task !== 'apply' && $task !== 'save2new' && $this->input->get('layout') !== 'modal') {
            // For default save task, redirect to list with menutype parameter.
            $return = 'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend()
                . '&menutype=' . $this->app->getUserState('com_menus.items.menutype');

            $this->setRedirect(Route::_($return, false));
        }
    }

    /**
     * Method to set the save success message.
     *
     * @param   int  $recordId  The record id.
     *
     * @return  void
     *
     * @since   6.1.0
     */
    protected function setSaveSuccessMessage($recordId): void
    {
        $this->setMessage(Text::_('COM_MENUS_SAVE_SUCCESS'));
    }

    /**
     * Sets the type of the menu item currently being edited.
     *
     * @return  void
     *
     * @since   1.6
     */
    public function setType()
    {
        $this->checkToken();

        $app = $this->app;

        // Get the posted values from the request.
        $data = $this->input->post->get('jform', [], 'array');

        // Get the type.
        $type = $data['type'];

        $type     = json_decode(base64_decode($type));
        $title    = $type->title ?? null;
        $recordId = $type->id ?? 0;

        $specialTypes = ['alias', 'separator', 'url', 'heading', 'container'];

        if (!\in_array($title, $specialTypes)) {
            $title = 'component';
        } else {
            // Set correct component id to ensure proper 404 messages with system links
            $data['component_id'] = 0;
        }

        $app->setUserState('com_menus.edit.item.type', $title);

        if ($title == 'component') {
            if (isset($type->request)) {
                // Clean component name
                $type->request->option = InputFilter::getInstance()->clean($type->request->option, 'CMD');

                $component            = ComponentHelper::getComponent($type->request->option);
                $data['component_id'] = $component->id;

                $app->setUserState('com_menus.edit.item.link', 'index.php?' . Uri::buildQuery((array) $type->request));
            }
        } elseif ($title == 'alias') {
            // If the type is alias you just need the item id from the menu item referenced.
            $app->setUserState('com_menus.edit.item.link', 'index.php?Itemid=');
        }

        unset($data['request']);

        $data['type'] = $title;

        if ($this->input->get('fieldtype') == 'type') {
            $data['link'] = $app->getUserState('com_menus.edit.item.link');
        }

        // Save the data in the session.
        $app->setUserState('com_menus.edit.item.data', $data);

        $this->setRedirect(
            Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId), false)
        );
    }

    /**
     * Gets the parent items of the menu location currently.
     *
     * @return  void
     *
     * @since   3.2
     */
    public function getParentItem()
    {
        $app = $this->app;

        $results  = [];
        $menutype = $this->input->get->get('menutype');

        if ($menutype) {
            /** @var \Joomla\Component\Menus\Administrator\Model\ItemsModel $model */
            $model = $this->getModel('Items', 'Administrator', []);
            $model->getState();
            $model->setState('filter.menutype', $menutype);
            $model->setState('list.select', 'a.id, a.title, a.level');
            $model->setState('list.start', '0');
            $model->setState('list.limit', '0');

            $results = $model->getItems();

            // Pad the option text with spaces using depth level as a multiplier.
            foreach ($results as $result) {
                $result->title = str_repeat(' - ', $result->level) . $result->title;
            }
        }

        // Output a \JSON object
        echo json_encode($results);

        $app->close();
    }
}
