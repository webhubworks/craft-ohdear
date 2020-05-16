<?php
/**
 * Test plugin for Craft CMS 3.x
 *
 * Integrate the *** into the Control Panel.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2020 Johannes Ahrndt
 */

namespace webhub\testtests\unit;

use Codeception\Test\Unit;
use UnitTester;
use Craft;
use webhub\test\Test;

/**
 * ExampleUnitTest
 *
 *
 * @author    Johannes Ahrndt
 * @package   Test
 * @since     1.0.0
 */
class ExampleUnitTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            Test::class,
            Test::$plugin
        );
    }

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }
}
