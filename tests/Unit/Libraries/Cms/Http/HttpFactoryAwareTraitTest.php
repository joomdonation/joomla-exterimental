<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Base
 *
 * @copyright   (C) 2025 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Libraries\Cms\Http;

use Joomla\CMS\Http\HttpFactoryAwareTrait;
use Joomla\CMS\Http\HttpFactoryInterface;
use Joomla\Http\Http;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;

/**
 * Test class for \Joomla\CMS\Http\HttpFactoryAwareTrait
 *
 * @package     Joomla.UnitTest
 * @subpackage  Http
 * @since       5.3.0
 */
class HttpFactoryAwareTraitTest extends UnitTestCase
{
    /**
     * @testdox  The HTTP client factory can be set and accessed by the trait
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetSetHttpFactory()
    {
        $mockHttp    = $this->createMock(Http::class);
        $httpFactory = new class ($mockHttp) implements HttpFactoryInterface {
            public function __construct(private readonly Http $http)
            {
            }

            public function createHttp(?Registry $options = null): Http
            {
                return $this->http;
            }
        };

        $trait = new class () {
            use HttpFactoryAwareTrait;

            public function getFactory(): HttpFactoryInterface
            {
                return $this->getHttpFactory();
            }
        };

        $trait->setHttpFactory($httpFactory);

        $this->assertEquals($httpFactory, $trait->getFactory());
    }

    /**
     * @testdox  Accessing the HTTP client factory without setting it throws an exception
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function testGetHttpFactoryThrowsException()
    {
        $this->expectException(\UnexpectedValueException::class);

        $trait = new class () {
            use HttpFactoryAwareTrait;

            public function getFactory(): HttpFactoryInterface
            {
                return $this->getHttpFactory();
            }
        };

        $trait->getFactory();
    }
}
