<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;

class Visitor
{
    private $details = array();

    public function __construct($details)
    {
        $this->details = $details;
    }

    public function getCustomDimensionValues($configuredVisitDimensions)
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