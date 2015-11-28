<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\Unit\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\CaseSensitive;

/**
 * @group CustomDimensions
 * @group CaseSensitiveTest
 * @group CaseSensitive
 * @group Plugins
 */
class CaseSensitiveTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value '' for 'caseSensitive' specified. Allowed values: '0' or '1'
     */
    public function test_check_shouldFailWhenActiveIsEmpty()
    {
        $this->buildCaseSensitive('')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value 'anyValUe' for 'caseSensitive' specified. Allowed values: '0' or '1'
     */
    public function test_check_shouldFailWhenActiveIsNotValid()
    {
        $this->buildCaseSensitive('anyValUe')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value '2'
     */
    public function test_check_shouldFailWhenActiveIsNumericButNot0or1()
    {
        $this->buildCaseSensitive('2')->check();
    }

    public function test_check_shouldNotFailWhenActiveIsValid()
    {
        $this->buildCaseSensitive(true)->check();
        $this->buildCaseSensitive(false)->check();
        $this->buildCaseSensitive(0)->check();
        $this->buildCaseSensitive(1)->check();
        $this->buildCaseSensitive('0')->check();
        $this->buildCaseSensitive('1')->check();
    }

    private function buildCaseSensitive($caseSensitive)
    {
        return new CaseSensitive($caseSensitive);
    }

}
