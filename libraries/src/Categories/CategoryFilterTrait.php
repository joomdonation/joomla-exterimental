<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Categories;

use Joomla\CMS\Table\Category;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Trait providing a reusable helper to apply a category / level filter to a list query.
 *
 * Use this trait in any ListModel subclass that needs to filter by filter.category_id
 * and filter.level model state against the #__categories table.
 *
 * @since  5.3.0
 */
trait CategoryFilterTrait
{
    /**
     * Applies filter.category_id (and optionally filter.level) to a query.
     *
     * The method handles three cases:
     * 1. category_id(s) provided with optional depth level → sub-tree WHERE clause
     * 2. only level provided                              → WHERE level <= X
     * 3. neither provided                                 → no filter added
     *
     * @param   QueryInterface  $query       The query to modify
     * @param   mixed           $categoryId  Single id, array of ids, or empty — from filter.category_id state
     * @param   mixed           $level       Maximum nesting depth — from filter.level state
     * @param   string          $tableAlias  Alias of the categories table in the query ('c' when joined, 'a' when
     *                                       the item IS the category row)
     *
     * @return  void
     *
     * @since   5.3.0
     */
    protected function filterQueryByCategoryId(
        QueryInterface $query,
        $categoryId,
        $level,
        string $tableAlias = 'c'
    ): void {
        $db = $this->getDatabase();

        if (!\is_array($categoryId)) {
            $categoryId = $categoryId ? [$categoryId] : [];
        }

        $level = (int) $level;

        if (\count($categoryId)) {
            // Case: Using both categories filter and optionally the level filter.
            $categoryId       = ArrayHelper::toInteger($categoryId);
            $categoryTable    = new Category($db);
            $subCatItemsWhere = [];

            foreach ($categoryId as $filterCatId) {
                $categoryTable->load($filterCatId);

                // bindArray is used so that multiple loop iterations don't overwrite each other's bindings.
                $valuesToBind = [(int) $categoryTable->lft, (int) $categoryTable->rgt];

                if ($level) {
                    $valuesToBind[] = $level + (int) $categoryTable->level - 1;
                }

                $bounded = $query->bindArray($valuesToBind);

                $categoryWhere = $db->quoteName($tableAlias . '.lft') . ' >= ' . $bounded[0]
                    . ' AND ' . $db->quoteName($tableAlias . '.rgt') . ' <= ' . $bounded[1];

                if ($level) {
                    $categoryWhere .= ' AND ' . $db->quoteName($tableAlias . '.level') . ' <= ' . $bounded[2];
                }

                $subCatItemsWhere[] = '(' . $categoryWhere . ')';
            }

            $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');
        } elseif ($level) {
            // Case: Using only the level filter.
            $query->where($db->quoteName($tableAlias . '.level') . ' <= :level')
                ->bind(':level', $level, ParameterType::INTEGER);
        }
    }
}
