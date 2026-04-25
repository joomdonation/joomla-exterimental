<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Http;

use Joomla\Http\Http;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Interface defining a factory which can create Http client objects.
 *
 * @since  5.3.0
 */
interface HttpFactoryInterface
{
    /**
     * Method to get an instance of a Http client.
     *
     * @param   ?Registry  $options  Client options.
     *
     * @return  Http
     *
     * @since   5.3.0
     */
    public function createHttp(?Registry $options = null): Http;
}
