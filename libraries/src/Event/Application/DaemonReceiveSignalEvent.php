<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Application;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class for ReceiveSignal event for DaemonApplication
 *
 * @since  5.0.0
 */
class DaemonReceiveSignalEvent extends ApplicationEvent
{

    /**
     * The names of event arguments that are required.
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    protected $requiredArguments = ['subject', 'signal'];

    /**
     * Constructor.
     *
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @throws  \BadMethodCallException
     *
     * @since   5.0.0
     */
    public function __construct($name, array $arguments = [])
    {
        parent::__construct($name, $arguments);
    }

    /**
     * Setter for the signal argument.
     *
     * @param   integer  $value  The value to set
     *
     * @return  integer
     *
     * @since  5.0.0
     */
    protected function onSetSignal(int $value): int
    {
        return $value;
    }

    /**
     * Get the event's signal object
     *
     * @return  integer
     *
     * @since  5.0.0
     */
    public function getSignal(): int
    {
        return $this->arguments['signal'];
    }
}
