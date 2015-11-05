<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomDimensions\tests\Commands;

use Piwik\Plugins\CustomDimensions\Commands\RemoveCustomDimension;
use Piwik\Plugins\CustomDimensions\CustomDimensions;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomDimensions
 * @group CustomDimensionsTest
 * @group Plugins
 * @group Plugins
 */
class RemoveCustomDimensionTest extends IntegrationTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  The specified scope is invalid. Use either
     */
    public function testExecute_ShouldThrowException_IfArgumentIsMissing()
    {
        $this->executeCommand(null, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  The specified scope is invalid. Use either "--scope=visit" or "--scope=action"
     */
    public function testExecute_ShouldThrowException_IfScopeIsInvalid()
    {
        $this->executeCommand('invalidscope', null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  An option "index" must be specified
     */
    public function testExecute_ShouldThrowException_IfIndexIsNotSpecifed()
    {
        $this->executeCommand(CustomDimensions::SCOPE_VISIT, null);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  Option "index" must be a number
     */
    public function testExecute_ShouldThrowException_IfIndexIsNotANumber()
    {
        $this->executeCommand(CustomDimensions::SCOPE_VISIT, '545fddfd');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  Specified index is not installed
     */
    public function testExecute_ShouldThrowException_IfCountIsLessThanONe()
    {
        $this->executeCommand(CustomDimensions::SCOPE_VISIT, '14');
    }

    public function testExecute_ShouldThrowException_IfUserCancelsConfirmation()
    {
        $result = $this->executeCommand(CustomDimensions::SCOPE_VISIT, $index = 5, false);
        $this->assertStringEndsWith('Are you sure you want to perform this action? (y/N)', $result);
    }

    public function testExecute_ShouldAddSpecifiedCount()
    {
        $logVisit = new LogTable(CustomDimensions::SCOPE_VISIT);
        $this->assertSame(range(1,5), $logVisit->getInstalledIndexes());

        $logConversion = new LogTable(CustomDimensions::SCOPE_CONVERSION);
        $this->assertSame(range(1,5), $logConversion->getInstalledIndexes());

        $logAction = new LogTable(CustomDimensions::SCOPE_ACTION);
        $this->assertSame(range(1,5), $logAction->getInstalledIndexes());

        $result = $this->executeCommand(CustomDimensions::SCOPE_ACTION, $index = 3);

        $this->assertContains('Remove Custom Dimension at index 3 in scope action.', $result);
        $this->assertContains('Are you sure you want to perform this action?', $result);
        $this->assertContains('Starting to remove this Custom Dimension', $result);
        $this->assertContains('Your Piwik is now configured for up to 4 Custom Dimensions in scope action.', $result);

        $logVisit = new LogTable(CustomDimensions::SCOPE_VISIT);
        $this->assertSame(range(1,5), $logVisit->getInstalledIndexes());

        $logConversion = new LogTable(CustomDimensions::SCOPE_CONVERSION);
        $this->assertSame(range(1,5), $logConversion->getInstalledIndexes());

        $logAction = new LogTable(CustomDimensions::SCOPE_ACTION);
        $this->assertSame(array(1,2,4,5), $logAction->getInstalledIndexes());
    }

    public function testExecute_ShouldAddSpecifiedCount_IfScopeIsVisitShouldAlsoUpdateConversion()
    {
        $logVisit = new LogTable(CustomDimensions::SCOPE_VISIT);
        $this->assertSame(range(1,5), $logVisit->getInstalledIndexes());

        $logConversion = new LogTable(CustomDimensions::SCOPE_CONVERSION);
        $this->assertSame(range(1,5), $logConversion->getInstalledIndexes());

        $result = $this->executeCommand(CustomDimensions::SCOPE_VISIT, $index = 2);

        $this->assertContains('Remove Custom Dimension at index 2 in scope visit', $result);
        $this->assertContains('Are you sure you want to perform this action?', $result);
        $this->assertContains('Starting to remove this Custom Dimension', $result);
        $this->assertContains('Your Piwik is now configured for up to 4 Custom Dimensions in scope visit.', $result);

        $logVisit = new LogTable(CustomDimensions::SCOPE_VISIT);
        $this->assertSame(array(1,3,4,5), $logVisit->getInstalledIndexes());

        $logConversion = new LogTable(CustomDimensions::SCOPE_CONVERSION);
        $this->assertSame(array(1,3,4,5), $logConversion->getInstalledIndexes());

        $logAction = new LogTable(CustomDimensions::SCOPE_ACTION);
        $this->assertSame(array(1,2,4,5), $logAction->getInstalledIndexes());
    }

    /**
     * @param string|null $scope
     * @param int|null $index
     * @param bool  $confirm
     *
     * @return string
     */
    private function executeCommand($scope, $index, $confirm = true)
    {
        $removeCustomDimension = new RemoveCustomDimension();

        $application = new Application();
        $application->add($removeCustomDimension);

        $commandTester = new CommandTester($removeCustomDimension);

        $dialog = $removeCustomDimension->getHelper('dialog');
        $dialog->setInputStream($this->getInputStream($confirm ? 'yes' : 'no' . '\n'));

        $params = array();
        if (!is_null($scope)) {
            $params['--scope'] = $scope;
        }

        if (!is_null($index)) {
            $params['--index'] = $index;
        }

        $params['command'] = $removeCustomDimension->getName();
        $commandTester->execute($params);
        $result = $commandTester->getDisplay();

        return $result;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
