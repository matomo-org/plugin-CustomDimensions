<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\Unit\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\Name;
use Piwik\Translate;

/**
 * @group CustomDimensions
 * @group NameTest
 * @group Name
 * @group Plugins
 */
class NameTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Translate::reset();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage CustomDimensions_NameIsRequired
     */
    public function test_check_shouldFailWhenNameIsEmpty()
    {
        $this->buildName('')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage CustomDimensions_NameAllowedCharacters
     * @dataProvider getInvalidNames
     */
    public function test_check_shouldFailWhenNameIsInvalid($name)
    {
        $this->buildName($name)->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage CustomDimensions_NameIsTooLong
     */
    public function test_check_shouldFailWhenNameIsTooLong()
    {
        $this->buildName(str_pad('test', 256, '434'))->check();
    }

    public function getInvalidNames()
    {
        return array(
            array('test.name'),
            array('.'),
            array('..'),
            array('../'),
            array('/'),
            array('<b>test</b>'),
            array('\\test'),
            array('/tmp'),
            array('&amp;'),
            array('<test'),
            array('Test>te'),
        );
    }

    /**
     * @dataProvider getValidNames
     */
    public function test_check_shouldNotFailWhenScopeIsValid($name)
    {
        $this->buildName($name)->check();
    }

    public function getValidNames()
    {
        return array(
            array('testname012ewewe er 54 -_ 454'),
            array('testname'),
            array('öüätestnam'),
        );
    }

    private function buildName($name)
    {
        return new Name($name);
    }

}
