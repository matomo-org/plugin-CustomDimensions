<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\Unit\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\Extractions;

/**
 * @group CustomDimensions
 * @group ExtractionsTest
 * @group Extractions
 * @group Plugins
 */
class ExtractionsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage extractions has to be an array
     */
    public function test_check_shouldFailWhenExtractionsIsNotAnArray()
    {
        $this->buildExtractions('')->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Each extraction within extractions has to be an array
     */
    public function test_check_shouldFailWhenExtractionsDoesNotContainArrays()
    {
        $this->buildExtractions(array('5'))->check();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Each extraction within extractions must have a key "dimension" and "pattern" only
     * @dataProvider getInvalidExtraction
     */
    public function test_check_shouldFailWhenExtractionsDoesNotContainValidExtraction($extraction)
    {
        $this->buildExtractions(array($extraction))->check();
    }

    public function getInvalidExtraction()
    {
        return array(
            array(array()),
            array(array('dimension' => 'url')),
            array(array('pattern' => 'index(.+).html')),
            array(array('dimension' => 'url', 'anything' => 'invalid')),
            array(array('dimension' => 'url', 'pattern' => 'index(.+).html', 'anything' => 'invalid')),
        );
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Invald dimension 'invalId' used in an extraction. Available dimensions are: url, urlparam, action_name
     */
    public function test_check_shouldAlsoCheckExtractionAndFailIfValueIsInvalid()
    {
        $extraction1 = array('dimension' => 'url', 'pattern' => 'index(.+).html');
        $extraction2 = array('dimension' => 'invalId', 'pattern' => 'index');
        $this->buildExtractions(array($extraction1, $extraction2))->check();
    }

    public function test_check_shouldNotFailWhenExtractionsDefinitionIsValid()
    {
        $extraction1 = array('dimension' => 'url', 'pattern' => 'index(.+).html');
        $extraction2 = array('dimension' => 'urlparam', 'pattern' => 'index');
        $this->buildExtractions(array($extraction1, $extraction2))->check();
    }

    private function buildExtractions($extractions)
    {
        return new Extractions($extractions);
    }

}
