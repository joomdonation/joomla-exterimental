<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Http;

use Joomla\CMS\Version;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory as FrameworkHttpFactory;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Default factory for creating Http client objects.
 *
 * @since  __DEPLOY_VERSION__
 */
class HttpClientFactory implements HttpFactoryInterface
{
    /**
     * Method to get an instance of a Http client with sensible Joomla defaults.
     *
     * The factory pre-populates the userAgent option using the Joomla version string
     * when no userAgent has been provided in the options.
     *
     * @param   ?Registry          $options   Client options.
     * @param   array|string|null  $adapters  Adapter (string) or queue of adapters (array) to use for communication.
     *
     * @return  Http
     *
     * @since   __DEPLOY_VERSION__
     * @throws  \RuntimeException
     */
    public function createHttp(?Registry $options = null, array|string|null $adapters = null): Http
    {
        $options = $options ?? new Registry();

        if (!$options->get('userAgent')) {
            $options->set('userAgent', (new Version())->getUserAgent('Joomla', true, false));
        }

        return (new FrameworkHttpFactory())->getHttp($options, $adapters);
    }
}
