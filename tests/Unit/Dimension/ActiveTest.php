<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\Unit\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\Active;

/**
 * @group CustomDimensions
 * @group ActiveTest
 * @group Active
 * @group Plugins
 */
class ActiveTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value '' for 'active' specified. Allowed values: '0' or '1'
     */
    public function test_check_shouldFailWhenActiveIsEmpty()
    {
        $this->buildActive('')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value 'anyValUe' for 'active' specified. Allowed values: '0' or '1'
     */
    public function test_check_shouldFailWhenActiveIsNotValid()
    {
        $this->buildActive('anyValUe')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value '2'
     */
    public function test_check_shouldFailWhenActiveIsNumericButNot0or1()
    {
        $this->buildActive('2')->check();
    }

    public function test_check_shouldNotFailWhenActiveIsValid()
    {
        $this->buildActive(true)->check();
        $this->buildActive(false)->check();
        $this->buildActive(0)->check();
        $this->buildActive(1)->check();
        $this->buildActive('0')->check();
        $this->buildActive('1')->check();
    }

    private function buildActive($active)
    {
        return new Active($active);
    }

}
