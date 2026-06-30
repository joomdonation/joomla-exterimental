<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Administrator\Controller;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Menu Type Controller
 *
 * @since  1.6
 */
class MenuController extends FormController
{
    /**
     * Dummy method to redirect back to standard controller
     *
     * @param   boolean  $cachable   If true, the view output will be cached.
     * @param   array    $urlparams  An array of safe URL parameters and their variable types.
     *                   @see        \Joomla\CMS\Filter\InputFilter::clean() for valid values.
     *
     * @return  void
     *
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect(Route::_('index.php?option=com_menus&view=menus', false));
    }

    /**
     * Method to save a menu item.
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

        $data     = $this->input->post->get('jform', [], 'array');
        $context  = 'com_menus.edit.menu';
        $recordId = $this->input->getInt('id');

        // Prevent using 'main' as menutype as this is reserved for backend menus
        if (strtolower($data['menutype']) == 'main') {
            $this->setMessage(Text::_('COM_MENUS_ERROR_MENUTYPE'), 'error');

            // Redirect back to the edit screen.
            $this->setRedirect(Route::_('index.php?option=com_menus&view=menu&layout=edit' . $this->getRedirectToItemAppend($recordId), false));

            return false;
        }

        $data['menutype'] = InputFilter::getInstance()->clean($data['menutype'], 'TRIM');

        // Store the preset value before validation (it will be removed by model).
        $preset = null;

        if (isset($data['preset'])) {
            $preset = trim($data['preset']) ?: null;
        }

        // Temporarily store preset in a class property to use in postSaveHook.
        $this->preset       = $preset;
        $this->presetClient = $data['client_id'] ?? 0;

        // Call parent save method to handle the rest.
        return parent::save('id', 'id');
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
        // Populate the id from the request.
        $data['id'] = $this->input->getInt('id');

        return $data;
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
        // Check if we imported a preset.
        if (isset($this->preset) && $this->preset && $this->presetClient == 1) {
            $this->setMessage(Text::_('COM_MENUS_PRESET_IMPORT_SUCCESS'));
        } else {
            $this->setMessage(Text::_('COM_MENUS_MENU_SAVE_SUCCESS'));
        }
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
        // Import the preset if selected.
        if (isset($this->preset) && $this->preset && $this->presetClient == 1) {
            // Menu Type has not been saved yet. Make sure items get the real menutype.
            $menutype = ApplicationHelper::stringURLSafe($validData['menutype']);

            try {
                MenusHelper::installPreset($this->preset, $menutype);
            } catch (\Exception $e) {
                // Save was successful but the preset could not be loaded. Let it through with just a warning
                $this->setMessage(Text::sprintf('COM_MENUS_PRESET_IMPORT_FAILED', $e->getMessage()));
            }
        }

        // Clean up temporary properties.
        unset($this->preset, $this->presetClient);
    }

    /**
     * Temporary property to store preset value.
     *
     * @var    string|null
     * @since  6.1.0
     */
    private $preset;

    /**
     * Temporary property to store client ID for preset.
     *
     * @var    int
     * @since  6.1.0
     */
    private $presetClient;

    /**
     * Method to display a menu as preset xml.
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   3.8.0
     */
    public function exportXml()
    {
        // Check for request forgeries.
        $this->checkToken();

        $cid = (array) $this->input->get('cid', [], 'int');

        // We know the first element is the one we need because we don't allow multi selection of rows
        $id = empty($cid) ? 0 : reset($cid);

        if ($id === 0) {
            $this->setMessage(Text::_('COM_MENUS_SELECT_MENU_FIRST_EXPORT'), 'warning');

            $this->setRedirect(Route::_('index.php?option=com_menus&view=menus', false));

            return false;
        }

        $model = $this->getModel('Menu');
        $item  = $model->getItem($id);

        if (!$item->menutype) {
            $this->setMessage(Text::_('COM_MENUS_SELECT_MENU_FIRST_EXPORT'), 'warning');

            $this->setRedirect(Route::_('index.php?option=com_menus&view=menus', false));

            return false;
        }

        $this->setRedirect(Route::_('index.php?option=com_menus&view=menu&menutype=' . $item->menutype . '&format=xml', false));

        return true;
    }
}
