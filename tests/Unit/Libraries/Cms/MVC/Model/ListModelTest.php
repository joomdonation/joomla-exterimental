<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  MVC
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\MVC\Model;

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for \Joomla\CMS\MVC\Model\ListModel
 *
 * @package     Joomla.UnitTest
 * @subpackage  MVC
 *
 * @testdox     The ListModel
 *
 * @since       5.3.0
 */
class ListModelTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a minimal concrete ListModel with $context exposed and state accessible.
     *
     * @param   string  $context  Model context used as part of the store-id hash.
     *
     * @return  ListModel  A concrete ListModel instance whose state can be read/written.
     */
    private function makeModel(string $context = 'test.model'): ListModel
    {
        $db = $this->createStub(DatabaseInterface::class);
        $db->method('createQuery')->willReturn($this->getQueryStub($db));

        $mvcFactory = $this->createStub(MVCFactoryInterface::class);

        return new class (
            ['dbo' => $db, 'ignore_request' => true],
            $mvcFactory,
            $context
        ) extends ListModel {
            /** @var string */
            private string $forcedContext;

            public function __construct($config, $factory, string $context)
            {
                parent::__construct($config, $factory);
                $this->forcedContext = $context;
                $this->context       = $context;
                // Mark state as populated so populateState() is not triggered.
                $this->__state_set = true;
            }

            /** Expose setState for test setup. */
            public function setFilterState(string $key, $value): void
            {
                $this->setState('filter.' . $key, $value);
            }

            /** Expose getStoreId for tests. */
            public function exposeStoreId(string $id = ''): string
            {
                return $this->getStoreId($id);
            }

            /** Expose filterQueryBySearch for tests. */
            public function exposeFilterQueryBySearch($query, string $idColumn, array $searchColumns): void
            {
                $this->filterQueryBySearch($query, $idColumn, $searchColumns);
            }
        };
    }

    // -------------------------------------------------------------------------
    // getStoreId – automatic filter state inclusion
    // -------------------------------------------------------------------------

    /**
     * @testdox  returns a non-empty hash when no filter state is set
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdWithNoFiltersIsNotEmpty(): void
    {
        $model = $this->makeModel();
        $this->assertNotEmpty($model->exposeStoreId());
    }

    /**
     * @testdox  returns different hashes for different filter.search values
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdDiffersForDifferentSearchValues(): void
    {
        $model1 = $this->makeModel();
        $model1->setFilterState('search', 'foo');

        $model2 = $this->makeModel();
        $model2->setFilterState('search', 'bar');

        $this->assertNotEquals($model1->exposeStoreId(), $model2->exposeStoreId());
    }

    /**
     * @testdox  returns the same hash regardless of the order filter states were set
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdIsOrderIndependent(): void
    {
        $model1 = $this->makeModel();
        $model1->setFilterState('search', 'hello');
        $model1->setFilterState('published', 1);

        $model2 = $this->makeModel();
        $model2->setFilterState('published', 1);
        $model2->setFilterState('search', 'hello');

        $this->assertEquals($model1->exposeStoreId(), $model2->exposeStoreId());
    }

    /**
     * @testdox  returns the same hash when the same filters are set
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdIsConsistentForSameFilters(): void
    {
        $model1 = $this->makeModel();
        $model1->setFilterState('search', 'test');
        $model1->setFilterState('published', 1);

        $model2 = $this->makeModel();
        $model2->setFilterState('search', 'test');
        $model2->setFilterState('published', 1);

        $this->assertEquals($model1->exposeStoreId(), $model2->exposeStoreId());
    }

    /**
     * @testdox  returns different hashes for the same filters in different contexts
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdDiffersForDifferentContexts(): void
    {
        $model1 = $this->makeModel('context.a');
        $model1->setFilterState('search', 'same');

        $model2 = $this->makeModel('context.b');
        $model2->setFilterState('search', 'same');

        $this->assertNotEquals($model1->exposeStoreId(), $model2->exposeStoreId());
    }

    /**
     * @testdox  the prefix $id parameter still produces a different hash
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetStoreIdWithDifferentPrefixesDiffer(): void
    {
        $model = $this->makeModel();
        $model->setFilterState('search', 'same');

        $this->assertNotEquals($model->exposeStoreId(''), $model->exposeStoreId('getTotal'));
    }

    // -------------------------------------------------------------------------
    // filterQueryBySearch
    // -------------------------------------------------------------------------

    /**
     * @testdox  adds no WHERE clause when filter.search is empty
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testFilterQueryBySearchDoesNothingWhenEmpty(): void
    {
        $db    = $this->createStub(DatabaseInterface::class);
        $query = $this->getQueryStub($db);
        $db->method('createQuery')->willReturn($query);

        $model = $this->makeModel();

        // filter.search is not set → should be a no-op.
        $model->exposeFilterQueryBySearch($query, 'a.id', ['a.title']);

        $this->assertEmpty((string) $query->where);
    }

    /**
     * @testdox  adds a numeric exact-match WHERE for the 'id:N' prefix
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testFilterQueryBySearchHandlesIdPrefix(): void
    {
        $db    = $this->createStub(DatabaseInterface::class);
        $db->method('quoteName')->willReturnArgument(0);
        $db->method('quote')->willReturnCallback(static fn ($v) => "'$v'");
        $query = $this->getQueryStub($db);
        $db->method('createQuery')->willReturn($query);

        $model = $this->makeModel();
        $model->setFilterState('search', 'id:42');

        $model->exposeFilterQueryBySearch($query, 'a.id', ['a.title', 'a.alias']);

        $whereSql = implode(' ', (array) $query->where);
        $this->assertStringContainsString('a.id', $whereSql);
        $this->assertStringContainsString(':searchId', $whereSql);
    }

    /**
     * @testdox  adds a LIKE WHERE clause for a plain search term
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testFilterQueryBySearchAddsLikeForPlainTerm(): void
    {
        $db    = $this->createStub(DatabaseInterface::class);
        $db->method('quoteName')->willReturnArgument(0);
        $db->method('quote')->willReturnCallback(static fn ($v) => "'$v'");
        $query = $this->getQueryStub($db);
        $db->method('createQuery')->willReturn($query);

        $model = $this->makeModel();
        $model->setFilterState('search', 'hello world');

        $model->exposeFilterQueryBySearch($query, 'a.id', ['a.title', 'a.alias']);

        $whereSql = implode(' ', (array) $query->where);
        $this->assertStringContainsString('LIKE', $whereSql);
        $this->assertStringContainsString('a.title', $whereSql);
        $this->assertStringContainsString('a.alias', $whereSql);
    }
}
