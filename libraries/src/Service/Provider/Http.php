<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Service\Provider;

use Joomla\CMS\Http\HttpClientFactory;
use Joomla\CMS\Http\HttpFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Service provider for the HTTP client factory dependency.
 *
 * @since  5.3.0
 */
class Http implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function register(Container $container)
    {
        $container->alias('http.factory', HttpFactoryInterface::class)
            ->alias(HttpClientFactory::class, HttpFactoryInterface::class)
            ->share(
                HttpFactoryInterface::class,
                function (Container $container) {
                    return new HttpClientFactory();
                },
                true
            );
    }
}
