<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Languages\Administrator\Controller;

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Languages Override Controller
 *
 * @since  2.5
 */
class OverrideController extends FormController
{
    /**
     * Method to edit an existing override.
     *
     * @param   string  $key     The name of the primary key of the URL variable (not used here).
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (not used here).
     *
     * @return  void
     *
     * @since   2.5
     */
    public function edit($key = null, $urlVar = null)
    {
        // Do not cache the response to this, its a redirect
        $this->app->allowCache(false);

        $cid     = (array) $this->input->post->get('cid', [], 'string');
        $context = "$this->option.edit.$this->context";

        // Get the constant name.
        $recordId = (\count($cid) ? $cid[0] : $this->input->get('id'));

        // Access check.
        if (!$this->allowEdit()) {
            $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
            $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));

            return;
        }

        $this->app->setUserState($context . '.data', null);
        $this->setRedirect('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($recordId, 'id'));
    }

    /**
     * Method to save an override.
     *
     * @param   string  $key     The name of the primary key of the URL variable (not used here).
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (not used here).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   2.5
     */
    public function save($key = null, $urlVar = null)
    {
        // Language overrides use 'id' as the key (constant name).
        // Call parent save method with the key set to 'id'.
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
        // Populate the id from the request (which may be a string constant name).
        $data['id'] = $this->input->get('id');

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
        $this->setMessage(Text::_('COM_LANGUAGES_VIEW_OVERRIDE_SAVE_SUCCESS'));
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
        $task = $this->getTask();

        // For 'apply' task, we need to redirect to the saved key instead of recordId.
        if ($task === 'apply' && isset($validData['key'])) {
            $this->setRedirect(
                Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend($validData['key'], 'id'), false)
            );
        }
    }

    /**
     * Method to cancel an edit.
     *
     * @param   string  $key  The name of the primary key of the URL variable (not used here).
     *
     * @return  void
     *
     * @since   2.5
     */
    public function cancel($key = null)
    {
        $this->checkToken();

        $context = "$this->option.edit.$this->context";

        $this->app->setUserState($context . '.data', null);
        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));
    }

    /**
     * Gets the URL arguments to append to an item redirect.
     *
     * @param   integer  $recordId  The primary key id for the item.
     * @param   string   $urlVar    The name of the URL variable for the id.
     *
     * @return  string  The arguments to append to the redirect URL.
     *
     * @since   6.1.0
     */
    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);

        $filterLanguage = $this->input->get('filter_language', '', 'cmd');

        if ($filterLanguage !== '') {
            $append .= '&filter_language=' . $filterLanguage;
        }

        $filterClient = $this->input->get('filter_client', null, 'int');

        if ($filterClient !== null) {
            $append .= '&filter_client=' . $filterClient;
        }

        $sourceKey = $this->input->get('source_key', '', 'cmd');

        if ($sourceKey !== '') {
            $append .= '&source_key=' . rawurlencode($sourceKey);
        }

        $sourceLanguage = $this->input->get('source_language', '', 'cmd');

        if ($sourceLanguage !== '') {
            $append .= '&source_language=' . $sourceLanguage;
        }

        $sourceText = $this->input->getString('source_text', '');

        if ($sourceText !== '') {
            $append .= '&source_text=' . rawurlencode($sourceText);
        }

        return $append;
    }
}
