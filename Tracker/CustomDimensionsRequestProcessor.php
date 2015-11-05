<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\Tracker;

use Piwik\Common;
use Piwik\Plugins\CustomDimensions\CustomDimensions;
use Piwik\Plugins\CustomDimensions\Dao;
use Piwik\Tracker\Action;
use Piwik\Tracker\Cache;
use Piwik\Tracker\Request;
use Piwik\Tracker\RequestProcessor;
use Piwik\Tracker\Visit\VisitProperties;
use Piwik\Url;
use Piwik\UrlHelper;

/**
 * Handles tracking of custom dimensions
 */
class CustomDimensionsRequestProcessor extends RequestProcessor
{

    public function onExistingVisit(&$valuesToUpdate, VisitProperties $visitProperties, Request $request)
    {
        $properties = $visitProperties->getProperties();

        foreach ($properties as $key => $property) {
            if (Dao\LogTable::isCustomDimensionColumn($key)) {
                $valuesToUpdate[$key] = $properties[$key];
            }
        }
    }

    public function afterRequestProcessed(VisitProperties $visitProperties, Request $request)
    {
        $customDimensions = self::getCachedCustomDimensions($request);
        $params = $request->getParams();

        foreach ($customDimensions as $customDimension) {
            $scope = $customDimension['scope'];
            $field = 'dimension' . $customDimension['idcustomdimension'];
            $dbField = Dao\LogTable::buildCustomDimensionColumnName($customDimension['index']);

            $value = Common::getRequestVar($field, '', 'string', $params);

            if ($value !== '') {
                $this->saveValueInCorrectScope($scope, $dbField, $value, $visitProperties, $request);
                continue;
            }

            $extractions = $customDimension['extractions'];
            if (is_array($extractions)) {
                foreach ($extractions as $extraction) {
                    if (!array_key_exists('dimension', $extraction) || !array_key_exists('pattern', $extraction)) {
                        continue;
                    }

                    $value = $this->getValueForDimension($request, $extraction['dimension']);
                    $value = $this->extractValue($value, $extraction['dimension'], $extraction['pattern']);

                    if (!isset($value) || '' === $value) {
                        continue;
                    }

                    $this->saveValueInCorrectScope($scope, $dbField, $value, $visitProperties, $request);
                    break;
                }
            }
        }
    }

    public static function getCachedCustomDimensions(Request $request)
    {
        $idSite = $request->getIdSite();
        $cache  = Cache::getCacheWebsiteAttributes($idSite);

        if (empty($cache['custom_dimensions'])) {
            // no custom dimensions set
            return array();
        }

        return $cache['custom_dimensions'];
    }

    private function saveValueInCorrectScope($scope, $dbField, $value, VisitProperties $visitProperties, Request $request)
    {
        if ($scope === CustomDimensions::SCOPE_VISIT) {
            $visitProperties->setProperty($dbField, $value);
        } elseif ($scope === CustomDimensions::SCOPE_ACTION) {
            /** @var Action $action */
            $action = $request->getMetadata('Actions', 'action');
            $action->setCustomField($dbField, $value);
        }
    }

    private function extractValue($dimensionValue, $dimensionName, $pattern)
    {
        if (!isset($dimensionValue) || '' === $dimensionValue) {
            return null;
        }

        if ($dimensionName === 'urlparam') {
            $query  = Url::getQueryStringFromUrl($dimensionValue);
            $params = UrlHelper::getArrayFromQueryString($query);

            if (array_key_exists($pattern, $params)) {
                return $params[$pattern];
            }
        } elseif (preg_match('/' . str_replace('/', '\/', $pattern) . '/', (string) $dimensionValue, $matches)) {
            // we could improve performance here I reckon by combining all patterns of all configs see eg http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html

            if (array_key_exists(1, $matches)) {
                return $matches[1];
            }
        }
    }

    private function getValueForDimension(Request $request, $requestedDimension)
    {
        /** @var Action $action */
        $action = $request->getMetadata('Actions', 'action');

        if (in_array($requestedDimension, array('url', 'urlparam'))) {
            if (!empty($action)) {
                $dimension = $action->getActionUrlRaw();
            } else {
                $dimension = $request->getParam('url');
            }
        } elseif ($requestedDimension === 'action_name' && !empty($action)) {
            $dimension = $action->getActionName();
        } else {
            $dimension = $request->getParam($requestedDimension);
        }

        return $dimension;
    }
}
