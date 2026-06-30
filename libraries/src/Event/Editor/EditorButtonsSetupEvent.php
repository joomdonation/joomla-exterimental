<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Event\Editor;

use Joomla\CMS\Editor\Button\ButtonsRegistryInterface;
use Joomla\CMS\Event\AbstractImmutableEvent;

/**
 * Editor setup event
 *
 * @since   5.0.0
 */
final class EditorButtonsSetupEvent extends AbstractImmutableEvent
{

    /**
     * The names of event arguments that are required.
     *
     * @var    array
     * @since  __DEPLOY_VERSION__
     */
    protected $requiredArguments = ['subject', 'editorType', 'disabledButtons'];

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
        }
        }
        }

        parent::__construct($name, $arguments);
    }

    /**
     * Setter for the subject argument.
     *
     * @param   ButtonsRegistryInterface  $value  The value to set
     *
     * @return  ButtonsRegistryInterface
     *
     * @since  5.0.0
     */
    protected function onSetSubject(ButtonsRegistryInterface $value): ButtonsRegistryInterface
    {
        return $value;
    }

    /**
     * Returns Buttons Registry instance.
     *
     * @return  ButtonsRegistryInterface
     *
     * @since  5.0.0
     */
    public function getButtonsRegistry(): ButtonsRegistryInterface
    {
        return $this->getArgument('subject');
    }

    /**
     * Setter for the Editor Type argument.
     *
     * @param   string  $value  The value to set
     *
     * @return  string
     *
     * @since  5.0.0
     */
    protected function onSetEditorType(string $value): string
    {
        return $value;
    }

    /**
     * Getter for the Editor Type argument.
     *
     * @return  string
     *
     * @since  5.0.0
     */
    public function getEditorType(): string
    {
        return $this->arguments['editorType'];
    }

    /**
     * Setter for the disabled buttons argument.
     *
     * @param   array  $value  The value to set
     *
     * @return  array
     *
     * @since  5.0.0
     */
    protected function onSetDisabledButtons(array $value): array
    {
        return $value;
    }

    /**
     * Getter for the disabled buttons argument.
     *
     * @return  array
     *
     * @since  5.0.0
     */
    public function getDisabledButtons(): array
    {
        return $this->arguments['disabledButtons'];
    }

    /**
     * Setter for the Editor ID argument.
     *
     * @param   string  $value  The value to set
     *
     * @return  string
     *
     * @since  5.0.0
     */
    protected function onSetEditorId(string $value): string
    {
        return $value;
    }

    /**
     * Getter for the Editor ID argument.
     *
     * @return  string
     *
     * @since  5.0.0
     */
    public function getEditorId(): string
    {
        return $this->arguments['editorId'] ?? '';
    }

    /**
     * Setter for the asset argument.
     *
     * @param   int  $value  The value to set
     *
     * @return  int
     *
     * @since  5.0.0
     */
    protected function onSetAsset(int $value): int
    {
        return $value;
    }

    /**
     * Getter for the asset argument.
     *
     * @return  int
     *
     * @since  5.0.0
     */
    public function getAsset(): int
    {
        return $this->arguments['asset'] ?? 0;
    }

    /**
     * Setter for the author argument.
     *
     * @param   int  $value  The value to set
     *
     * @return  int
     *
     * @since  5.0.0
     */
    protected function onSetAuthor(int $value): int
    {
        return $value;
    }

    /**
     * Getter for the author argument.
     *
     * @return  int
     *
     * @since  5.0.0
     */
    public function getAuthor(): int
    {
        return $this->arguments['author'] ?? 0;
    }
}
