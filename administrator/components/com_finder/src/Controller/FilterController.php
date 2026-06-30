<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   (C) 2011 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Finder\Administrator\Controller;

use Joomla\CMS\Application\CMSWebApplicationInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Indexer controller class for Finder.
 *
 * @since  2.5
 */
class FilterController extends FormController
{
    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   2.5
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        $this->checkToken();

        $model   = $this->getModel();
        $table   = $model->getTable();
        $context = "$this->option.edit.$this->context";

        // Determine the name of the primary key for the data.
        if (empty($key)) {
            $key = $table->getKeyName();
        }

        // To avoid data collisions the urlVar may be different from the primary key.
        if (empty($urlVar)) {
            $urlVar = $key;
        }

        $recordId = $this->input->get($urlVar, '', 'int');

        // Check if the record is held for editing.
        if (!$this->checkEditId($context, $recordId)) {
            // Somehow the person just went to the form and tried to save it. We don't allow that.
            $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId), 'error');
            $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));

            return false;
        }

        // Call parent save method to handle the rest.
        return parent::save($key, $urlVar);
    }

    /**
     * Function that allows child controller to modify validated data before saving.
     *
     * @param   \Joomla\CMS\MVC\Model\BaseDatabaseModel  $model      The data model object.
     * @param   array                                    $validData  The validated data.
     *
     * @return  array  The modified validated data.
     *
     * @since   6.1.0
     */
    protected function preSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, array $validData = []): array
    {
        // Get and sanitize the filter data.
        $validData['data'] = $this->input->post->get('t', [], 'array');
        $validData['data'] = array_unique($validData['data']);
        $validData['data'] = ArrayHelper::toInteger($validData['data']);

        // Remove any values of zero.
        if (array_search(0, $validData['data'], true) !== false) {
            unset($validData['data'][array_search(0, $validData['data'], true)]);
        }

        return $validData;
    }
}
