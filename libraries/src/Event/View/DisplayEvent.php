<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\View;

use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\CMS\MVC\View\ViewInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Event class for WebAsset events
 *
 * @since  4.0.0
 */
class DisplayEvent extends AbstractImmutableEvent
{

    /**
     * The names of event arguments that are required.
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    protected $requiredArguments = ['subject'];

    /**
     * Constructor.
     *
     * @param   string  $name       The event name.
     * @param   array   $arguments  The event arguments.
     *
     * @throws  \BadMethodCallException
     *
     * @since   4.0.0
     */
    public function __construct($name, array $arguments = [])
    {
        if (!isset($arguments['subject'])) {
        }

        if (!($arguments['subject'] instanceof ViewInterface)) {
            throw new \BadMethodCallException("Argument 'subject' of event {$this->name} is not of type 'ViewInterface'");
        }

        if (!isset($arguments['extension'])) {
        }

        if (!isset($arguments['extension']) || !\is_string($arguments['extension'])) {
            throw new \BadMethodCallException("Argument 'extension' of event {$this->name} is not of type 'string'");
        }

        if (!str_contains($arguments['extension'], '.')) {
            throw new \BadMethodCallException("Argument 'extension' of event {$this->name} has wrong format. Valid format: 'component.section'");
        }
parent::__construct($name, $arguments);
    }
