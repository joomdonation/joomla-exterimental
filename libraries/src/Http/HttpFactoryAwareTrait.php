<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Http;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Defines the trait for an HttpFactoryInterface aware class.
 *
 * @since  5.3.0
 */
trait HttpFactoryAwareTrait
{
    /**
     * HttpFactoryInterface
     *
     * @var    HttpFactoryInterface
     * @since  5.3.0
     */
    private $httpFactory;

    /**
     * Get the HttpFactoryInterface.
     *
     * @return  HttpFactoryInterface
     *
     * @since   5.3.0
     * @throws  \UnexpectedValueException May be thrown if the HttpFactory has not been set.
     */
    protected function getHttpFactory(): HttpFactoryInterface
    {
        if ($this->httpFactory) {
            return $this->httpFactory;
        }

        throw new \UnexpectedValueException('HttpFactory not set in ' . __CLASS__);
    }

    /**
     * Set the HTTP client factory to use.
     *
     * @param   ?HttpFactoryInterface  $httpFactory  The HTTP client factory to use.
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function setHttpFactory(?HttpFactoryInterface $httpFactory = null): void
    {
        $this->httpFactory = $httpFactory;
    }
}
