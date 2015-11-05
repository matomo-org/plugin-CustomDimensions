<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\ArchiveProcessor;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;
use Piwik\Tracker\Cache;
use Piwik\Tracker;

class CustomDimensions extends \Piwik\Plugin
{
    const SCOPE_ACTION = 'action';
    const SCOPE_VISIT = 'visit';
    const SCOPE_CONVERSION = 'conversion';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configuration = new Configuration();
    }

    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'API.getSegmentDimensionMetadata' => 'getSegmentsMetadata',
            'Live.getAllVisitorDetails'       => 'extendVisitorDetails',
            'Tracker.Cache.getSiteAttributes' => 'addCustomDimensionsAttributes',
            'SitesManager.deleteSite.end'     => 'deleteCustomDimensionDefinitionsForSite',
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
            'AssetManager.getStylesheetFiles' => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'ViewDataTable.configure' => 'configureViewDataTable',
            'API.getReportMetadata' => 'addReportMetadata',
            'Goals.getReportsWithGoalMetrics' => 'getReportsWithGoalMetrics',
            'Tracker.newConversionInformation' => 'addConversionInformation',
            'Tracker.getVisitFieldsToPersist' => 'addVisitFieldsToPersist',
            'Tracker.setTrackerCacheGeneral' => 'setTrackerCacheGeneral',
        );
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/model.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/list.controller.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/list.directive.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/edit.controller.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/edit.directive.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/manage.controller.js";
        $jsFiles[] = "plugins/CustomDimensions/angularjs/manage/manage.directive.js";
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/CustomDimensions/angularjs/manage/list.directive.less";
    }

    public function install()
    {
        $config = new Dao\Configuration();
        $config->install();

        foreach (self::getScopes() as $scope) {
            $tracking = new Dao\LogTable($scope);
            $tracking->install();
        }
    }

    public function uninstall()
    {
        $config = new Dao\Configuration();
        $config->uninstall();

        foreach (self::getScopes() as $scope) {
            $tracking = new Dao\LogTable($scope);
            $tracking->uninstall();
        }
    }

    public function extendVisitorDetails(&$visitor, $details)
    {
        $idSite = $visitor['idSite'];
        $dimensions = $this->configuration->getCustomDimensionsHavingScope($idSite, self::SCOPE_VISIT);

        foreach ($dimensions as $dimension) {
            $column = 'dimension' . $dimension['idcustomdimension'];
            $visitor[$column] = $details[$column];
        }
    }

    public function addCustomDimensionsAttributes(&$content, $idSite)
    {
        $content['custom_dimensions'] = $this->configuration->getCustomDimensionsForSite($idSite);
    }

    public function deleteCustomDimensionDefinitionsForSite($idSite)
    {
        $this->configuration->deleteConfigurationsForSite($idSite);
    }

    /**
     * There are also some hardcoded places in JavaScript
     * @return int
     */
    public static function getMaxLengthCustomDimensions()
    {
        return 255;
    }

    public function getSegmentsMetadata(&$segments, $idSites)
    {
        if (is_array($idSites) && count($idSites) !== 1) {
            return array();
        }

        if (is_array($idSites)) {
            $idSite = array_shift($idSites);
        } else {
            $idSite = $idSites;
        }

        $idSite = (int) $idSite;

        $dimensions = $this->configuration->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $dimension) {
            if ($dimension['scope'] === CustomDimensions::SCOPE_ACTION) {
                $table = 'log_link_visit_action';
            } elseif ($dimension['scope'] === CustomDimensions::SCOPE_VISIT) {
                $table = 'log_visit';
            } else {
                continue;
            }

            $segments[] = array(
                'type'       => 'dimension',
                'category'   => 'CustomDimensions_CustomDimensions',
                'name'       => $dimension['name'],
                'segment'    => 'dimension' . $dimension['idcustomdimension'],
                'sqlSegment' => $table . '.' . LogTable::buildCustomDimensionColumnName($dimension['index']),
            );
        }
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'General_Loading';
    }

    public function configureViewDataTable(ViewDataTable $view)
    {
        if ($this->pluginName == $view->requestConfig->getApiModuleToRequest()) {
            $view->config->addTranslations(Metrics::getDefaultMetricTranslations());
        }
    }

    public function addReportMetadata(&$availableReports, $parameters)
    {
        $idSites = $parameters['idSites'];

        if (is_array($idSites) && count($idSites) !== 1) {
            return;
        }

        $idSite = array_shift($idSites);

        $dimensions = $this->configuration->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $dimension) {
            $availableReports[] = array(
                'category' => 'CustomDimensions_CustomDimensions',
                'name'     => $dimension['name'],
                'module'   => $this->pluginName,
                'action'   => 'getCustomDimension',
                'parameters' => array('idDimension' => $dimension['idcustomdimension']),
                'dimension' => 'dimension' . $dimension['idcustomdimension'],
                'order' => $dimension['idcustomdimension']
            );
        }
    }

    public function getReportsWithGoalMetrics(&$reportsWithGoals)
    {
        $idSite = Common::getRequestVar('idSite', null, 'int');

        $dimensions = $this->configuration->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $dimension) {
            if ($dimension['scope'] !== self::SCOPE_VISIT) {
                continue;
            }

            $reportsWithGoals[] = array(
                'category' => 'CustomDimensions_CustomDimensions',
                'name'     => $dimension['name'],
                'module'   => $this->pluginName,
                'action'   => 'getCustomDimension',
                'parameters' => array('idDimension' => $dimension['idcustomdimension'])
            );
        }
    }

    public function addConversionInformation(&$conversion, $visitInformation, Tracker\Request $request)
    {
        $customDimensions = CustomDimensionsRequestProcessor::getCachedCustomDimensions($request);

        foreach ($customDimensions as $dimension) {
            if ($dimension['scope'] === self::SCOPE_VISIT) {
                $field = LogTable::buildCustomDimensionColumnName($dimension['index']);

                if (array_key_exists($field, $visitInformation)) {
                    $conversion[$field] = $visitInformation[$field];
                }
            }
        }
    }

    public function addVisitFieldsToPersist(&$fields)
    {
        $cache = Cache::getCacheGeneral();
        $key = 'custom_dimension_indexes_installed_' . self::SCOPE_VISIT;

        if (empty($cache[$key])) {
            return;
        }

        $indexes = $cache[$key];

        foreach ($indexes as $index) {
            $fields[] = LogTable::buildCustomDimensionColumnName($index);
        }
    }

    public function setTrackerCacheGeneral(&$cacheContent)
    {
        foreach (self::getScopes() as $scope) {
            $tracking = new LogTable($scope);
            $cacheContent['custom_dimension_indexes_installed_' . $scope] = $tracking->getInstalledIndexes();
        }
    }

    public static function getScopes()
    {
        return array(self::SCOPE_ACTION, self::SCOPE_VISIT, self::SCOPE_CONVERSION);
    }
}
