<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions\Columns;

use Piwik\Columns\Dimension;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Plugin\Segment;
use Piwik\Plugins\CustomDimensions\CustomDimensions;
use Piwik\Plugins\CustomDimensions\Dao\AutoSuggest;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;

class CustomDimension extends Dimension
{
    protected function configureSegments()
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');

        if (empty($idSite)) {
            return array();
        }

        $configuration = StaticContainer::get('Piwik\Plugins\CustomDimensions\Dao\Configuration');
        $dimensions = $configuration->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $dimension) {
            if (!$dimension['active']) {
                continue;
            }

            $segment = new Segment();
            $segment->setSegment(CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension));
            $segment->setType(Segment::TYPE_DIMENSION);
            $segment->setName($dimension['name']);

            $columnName = LogTable::buildCustomDimensionColumnName($dimension);

            if ($dimension['scope'] === CustomDimensions::SCOPE_ACTION) {
                $segment->setSqlSegment('log_link_visit_action. ' . $columnName);
                $segment->setCategory('General_Actions');
                $segment->setSuggestedValuesCallback(function ($idSite, $maxValuesToReturn) use ($dimension) {
                    $autoSuggest = new AutoSuggest();
                    return $autoSuggest->getMostUsedActionDimensionValues($dimension, $idSite, $maxValuesToReturn);
                });
            } elseif ($dimension['scope'] === CustomDimensions::SCOPE_VISIT) {
                $segment->setSqlSegment('log_visit. ' . $columnName);
                $segment->setCategory('General_Visit');
            } else {
                continue;
            }

            $this->addSegment($segment);
        }
    }

    public function getName()
    {
        return '';
    }

}