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
use Piwik\View;

class VisitorDetails extends VisitorDetailsAbstract
{
    public function extendVisitorDetails(&$visitor)
    {
        if (empty($visitor['idSite'])) {
            return;
        }

        $idSite     = $visitor['idSite'];
        $dimensions = $this->getActiveCustomDimensionsInScope($idSite, CustomDimensions::SCOPE_VISIT);

        foreach ($dimensions as $dimension) {
            // field in DB, eg custom_dimension_1
            $field = LogTable::buildCustomDimensionColumnName($dimension);
            // field for user, eg dimension1
            $column = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension);
            if (array_key_exists($field, $this->details)) {
                $visitor[$column] = $this->details[$field];
            } else {
                $visitor[$column] = null;
            }
        }
    }

    public function extendActionDetails(&$action, $nextAction, $visitorDetails)
    {
        if (empty($visitorDetails['idSite'])) {
            return;
        }

        $idSite     = $visitorDetails['idSite'];
        $dimensions = $this->getActiveCustomDimensionsInScope($idSite, CustomDimensions::SCOPE_ACTION);

        foreach ($dimensions as $dimension) {
            // field in DB, eg custom_dimension_1
            $field = LogTable::buildCustomDimensionColumnName($dimension);
            // field for user, eg dimension1
            $column = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension);

            if (array_key_exists($field, $action)) {
                $action[$column] = $action[$field];
            } else {
                $action[$column] = null;
            }
            unset($action[$field]);
        }

        static $indices;

        if (is_null($indices)) {
            $logTable = new Dao\LogTable(CustomDimensions::SCOPE_ACTION);
            $indices  = $logTable->getInstalledIndexes();
        }

        foreach ($indices as $index) {
            $field    = Dao\LogTable::buildCustomDimensionColumnName($index);
            unset($action[$field]);
        }
    }

    public function renderVisitorDetails($visitorDetails)
    {
        if (empty($visitorDetails['idSite'])) {
            return '';
        }

        $idSite           = $visitorDetails['idSite'];
        $dimensions       = $this->getActiveCustomDimensionsInScope($idSite, CustomDimensions::SCOPE_VISIT);
        $customDimensions = array();

        if (count($dimensions) > 0) {
            foreach ($dimensions as $dimension) {
                $column             = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension);
                $customDimensions[] = array(
                    'id'    => $dimension['idcustomdimension'],
                    'name'  => $dimension['name'],
                    'value' => $visitorDetails[$column]
                );
            }
        }

        $view                   = new View('@CustomDimensions/_visitorDetails');
        $view->visitInfo        = $visitorDetails;
        $view->customDimensions = $customDimensions;
        return $view->render();
    }

    public function renderActionTooltip($action, $visitInfo)
    {
        $idSite           = $visitInfo['idSite'];
        $dimensions       = $this->getActiveCustomDimensionsInScope($idSite, CustomDimensions::SCOPE_ACTION);
        $customDimensions = array();

        foreach ($dimensions as $dimension) {
            $column                               = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension);
            $customDimensions[$dimension['name']] = $action[$column];
        }

        if (!empty($customDimensions)) {
            $action['customDimensions'] = $customDimensions;
        }

        $view         = new View('@CustomDimensions/_actionTooltip');
        $view->action = $action;
        return $view->render();
    }

    protected $activeCustomDimensionsCache = array();

    protected function getActiveCustomDimensionsInScope($idSite, $scope)
    {
        if (array_key_exists($idSite.$scope, $this->activeCustomDimensionsCache)) {
            return $this->activeCustomDimensionsCache[$idSite.$scope];
        }

        $configuration = new Configuration();
        $dimensions    = $configuration->getCustomDimensionsHavingScope($idSite, $scope);
        $dimensions    = array_filter($dimensions, function ($dimension) use ($scope) {
            return ($dimension['active'] && $dimension['scope'] === $scope);
        });

        $this->activeCustomDimensionsCache[$idSite.$scope] = $dimensions;
        return $this->activeCustomDimensionsCache[$idSite.$scope];
    }
}