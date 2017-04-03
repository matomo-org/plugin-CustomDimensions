<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;
use Piwik\Plugins\Live\VisitorDetailsAbstract;

class VisitorDetails extends VisitorDetailsAbstract
{
    public function extendVisitorDetails(&$visitor)
    {
        if (empty($visitor['idSite'])) {
            return;
        }

        $idSite        = $visitor['idSite'];
        $configuration = new Configuration();
        $dimensions    = $configuration->getCustomDimensionsHavingScope($idSite, CustomDimensions::SCOPE_VISIT);

        $values = $this->getCustomDimensionValues($dimensions);

        foreach ($values as $field => $value) {
            $visitor[$field] = $value;
        }
    }

    protected function getCustomDimensionValues($configuredVisitDimensions)
    {
        $values = array();

        foreach ($configuredVisitDimensions as $dimension) {
            if ($dimension['active'] && $dimension['scope'] === CustomDimensions::SCOPE_VISIT) {
                // field in DB, eg custom_dimension_1
                $field = LogTable::buildCustomDimensionColumnName($dimension);
                // field for user, eg dimension1
                $column = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension);

                if (array_key_exists($field, $this->details)) {
                    $values[$column] = $this->details[$field];
                } else {
                    $values[$column] = null;
                }
            }
        }

        return $values;
    }
}