<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Tag;

use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Trait providing a reusable helper to apply a tag filter to a list query.
 *
 * Use this trait in any ListModel subclass that needs to filter by filter.tag
 * model state against the #__contentitem_tag_map table.
 *
 * @since  5.3.0
 */
trait TagFilterTrait
{
    /**
     * Applies the filter.tag model state to a query by joining #__contentitem_tag_map.
     *
     * Supported $tag shapes
     * - null / empty string / 0 with no special meaning → no filter applied
     * - array of integers                               → items must have ALL of those tags (INNER join)
     * - array containing 0 (with other ids)            → items with any of those tags OR items with no tags at all
     * - integer 0                                       → items with no tags at all
     * - single integer > 0                              → items with exactly that tag
     *
     * The method assumes the primary item table is aliased as `a` in the query.
     *
     * @param   QueryInterface  $query      The query to modify
     * @param   string          $typeAlias  The content type alias, e.g. 'com_content.article'
     * @param   mixed           $tag        The tag value(s), typically from $this->getState('filter.tag')
     *
     * @return  void
     *
     * @since   5.3.0
     */
    protected function filterQueryByTag(QueryInterface $query, string $typeAlias, $tag): void
    {
        $db = $this->getDatabase();

        // Run simplified query when filtering by one tag.
        if (\is_array($tag) && \count($tag) === 1) {
            $tag = $tag[0];
        }

        if ($tag && \is_array($tag)) {
            $tag         = ArrayHelper::toInteger($tag);
            $includeNone = \in_array(0, $tag);

            if ($includeNone) {
                $tag = array_filter($tag);
            }

            $subQuery = $db->createQuery()
                ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                ->from($db->quoteName('#__contentitem_tag_map'))
                ->where(
                    [
                        $db->quoteName('tag_id') . ' IN (' . implode(',', $query->bindArray($tag)) . ')',
                        $db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias),
                    ]
                );

            $query->join(
                $includeNone ? 'LEFT' : 'INNER',
                '(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
                $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
            );

            if ($includeNone) {
                $subQuery2 = $db->createQuery()
                    ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                    ->from($db->quoteName('#__contentitem_tag_map'))
                    ->where($db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias));

                $query->join(
                    'LEFT',
                    '(' . $subQuery2 . ') AS ' . $db->quoteName('tagmap2'),
                    $db->quoteName('tagmap2.content_item_id') . ' = ' . $db->quoteName('a.id')
                )
                ->where(
                    '(' . $db->quoteName('tagmap.content_item_id') . ' IS NOT NULL OR '
                    . $db->quoteName('tagmap2.content_item_id') . ' IS NULL)'
                );
            }
        } elseif (\is_numeric($tag)) {
            $tag = (int) $tag;

            if ($tag === 0) {
                $subQuery = $db->createQuery()
                    ->select('DISTINCT ' . $db->quoteName('content_item_id'))
                    ->from($db->quoteName('#__contentitem_tag_map'))
                    ->where($db->quoteName('type_alias') . ' = ' . $db->quote($typeAlias));

                // Only show items without tags.
                $query->join(
                    'LEFT',
                    '(' . $subQuery . ') AS ' . $db->quoteName('tagmap'),
                    $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                )
                ->where($db->quoteName('tagmap.content_item_id') . ' IS NULL');
            } else {
                $query->join(
                    'INNER',
                    $db->quoteName('#__contentitem_tag_map', 'tagmap'),
                    $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                )
                ->where(
                    [
                        $db->quoteName('tagmap.tag_id') . ' = :tag',
                        $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote($typeAlias),
                    ]
                )
                ->bind(':tag', $tag, ParameterType::INTEGER);
            }
        }
    }
}
