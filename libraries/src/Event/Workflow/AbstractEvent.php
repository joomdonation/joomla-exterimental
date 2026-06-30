<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2020 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Workflow;

use Joomla\CMS\Event\AbstractImmutableEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Event class for WebAsset events
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
    protected $requiredArguments = ['subject', 'extension'];

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
        if (!str_contains($arguments['extension'] ?? '', '.')) {
            throw new \BadMethodCallException("Argument 'extension' of event {$this->name} has wrong format. Valid format: 'component.section'");
        }

        if (!\array_key_exists('extensionName', $arguments) || !\array_key_exists('section', $arguments)) {
            $parts = explode('.', $arguments['extension']);

            $arguments['extensionName'] ??= $parts[0];
            $arguments['section']       ??= $parts[1];
        }

        parent::__construct($name, $arguments);
    }
