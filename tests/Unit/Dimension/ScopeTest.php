<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\Unit\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\Scope;

/**
 * @group CustomDimensions
 * @group ScopeTest
 * @group Scope
 * @group Plugins
 */
class ScopeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value '' for 'scope' specified. Available scopes are: visit, action, conversion
     */
    public function test_check_shouldFailWhenScopeIsEmpty()
    {
        $this->buildScope('')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid value 'anyScoPe' for 'scope' specified. Available scopes are: visit, action, conversion
     */
    public function test_check_shouldFailWhenScopeIsNotValid()
    {
        $this->buildScope('anyScoPe')->check();
    }

    public function test_check_shouldNotFailWhenScopeIsValid()
    {
        $this->buildScope('action')->check();
        $this->buildScope('visit')->check();
        $this->buildScope('conversion')->check();
    }

    private function buildScope($scope)
    {
        return new Scope($scope);
    }

}
