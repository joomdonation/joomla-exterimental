<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Table;

use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\CMS\Table\TableInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Event class for the Table's events
 *
 * @since  4.0.0
 */
abstract class AbstractEvent extends AbstractImmutableEvent
{
    /**
     * The names of event arguments that are required.
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    protected $requiredArguments = ['subject'];

    /**
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @throws  \BadMethodCallException
     *
     * @since   1.0
     */
    public function __construct($name, array $arguments = [])
    {
        parent::__construct($name, $arguments);
    }

    /**
     * Setter for the subject argument
     *
     * @param   TableInterface  $value  The value to set
     *
     * @return  TableInterface
     *
     * @throws  \BadMethodCallException  If the argument is not of the expected type.
     *
     * @deprecated 4.4.0 will be removed in 7.0
     *                Use counterpart with onSet prefix
     */
    protected function setSubject($value)
    {
        if (!\is_object($value) || !($value instanceof TableInterface)) {
            throw new \BadMethodCallException("Argument 'subject' of event {$this->name} is not of the expected type");
        }

        return $value;
    }

    /**
     * Setter for the subject argument
     *
     * @param   TableInterface  $value  The value to set
     *
     * @return  TableInterface
     *
     * @throws  \BadMethodCallException  If the argument is not of the expected type.
     *
     * @since  4.4.0
     */
    protected function onSetSubject($value): TableInterface
    {
        return $this->setSubject($value);
    }
}
