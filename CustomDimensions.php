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
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;
use Piwik\Tracker\Cache;
use Piwik\Tracker;
use Piwik\Plugin;

class CustomDimensions extends Plugin
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
            'API.getSegmentDimensionMetadata'  => 'getSegmentsMetadata',
            'Live.getAllVisitorDetails'        => 'extendVisitorDetails',
            'Tracker.Cache.getSiteAttributes'  => 'addCustomDimensionsAttributes',
            'SitesManager.deleteSite.end'      => 'deleteCustomDimensionDefinitionsForSite',
            'AssetManager.getJavaScriptFiles'  => 'getJsFiles',
            'AssetManager.getStylesheetFiles'  => 'getStylesheetFiles',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Goals.getReportsWithGoalMetrics'  => 'getReportsWithGoalMetrics',
            'Tracker.newConversionInformation' => 'addConversionInformation',
            'Tracker.getVisitFieldsToPersist'  => 'addVisitFieldsToPersist',
            'Tracker.setTrackerCacheGeneral'   => 'setTrackerCacheGeneral',
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
        $stylesheets[] = "plugins/CustomDimensions/angularjs/manage/edit.directive.less";
        $stylesheets[] = "plugins/CustomDimensions/angularjs/manage/list.directive.less";
        $stylesheets[] = "plugins/CustomDimensions/stylesheets/reports.less";
    }

    public function install()
    {
        $config = new Dao\Configuration();
        $config->install();

        foreach (self::getScopes() as $scope) {
            $tracking = new Dao\LogTable($scope);
            $tracking->install();
        }

        Cache::clearCacheGeneral();
    }

    public function uninstall()
    {
        $config = new Dao\Configuration();
        $config->uninstall();

        foreach (self::getScopes() as $scope) {
            $tracking = new Dao\LogTable($scope);
            $tracking->uninstall();
        }

        Cache::clearCacheGeneral();
    }

    public function extendVisitorDetails(&$visitor, $details)
    {
        if (empty($visitor['idSite'])) {
            return;
        }

        $idSite = $visitor['idSite'];
        $dimensions = $this->configuration->getCustomDimensionsHavingScope($idSite, self::SCOPE_VISIT);

        $visit  = new Visitor($details);
        $values = $visit->getCustomDimensionValues($dimensions);

        foreach ($values as $field => $value) {
            $visitor[$field] = $value;
        }
    }

    public function addCustomDimensionsAttributes(&$content, $idSite)
    {
        $dimensions = $this->configuration->getCustomDimensionsForSite($idSite);
        $active = array();

        foreach ($dimensions as $dimension) {
            if (!$dimension['active']) {
                continue;
            }

            $active[] = $dimension;
        }

        $content['custom_dimensions'] = $active;
    }

    public function deleteCustomDimensionDefinitionsForSite($idSite)
    {
        $this->configuration->deleteConfigurationsForSite($idSite);
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
            if (!$dimension['active']) {
                continue;
            }

            if ($dimension['scope'] === CustomDimensions::SCOPE_ACTION) {
                $table    = 'log_link_visit_action';
                $category = 'General_Actions';
            } elseif ($dimension['scope'] === CustomDimensions::SCOPE_VISIT) {
                $table    = 'log_visit';
                $category = 'General_Visit';
            } else {
                continue;
            }

            $segments[] = array(
                'type'       => 'dimension',
                'category'   => $category,
                'name'       => $dimension['name'],
                'segment'    => CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($dimension),
                'sqlSegment' => $table . '.' . LogTable::buildCustomDimensionColumnName($dimension),
            );
        }
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'General_Loading';
        $translationKeys[] = 'General_Id';
        $translationKeys[] = 'General_Name';
        $translationKeys[] = 'General_Action';
        $translationKeys[] = 'General_Cancel';
        $translationKeys[] = 'CorePluginsAdmin_Active';
        $translationKeys[] = 'Actions_ColumnPageURL';
        $translationKeys[] = 'Goals_PageTitle';

        // we simply make all translations available via JS as > 90% of them are used in JS anyway
        $translator = StaticContainer::get('Piwik\Translation\Translator');
        $t = $translator->getAllTranslations();
        foreach (array_keys($t[$this->pluginName]) as $key) {
            $translationKeys[] = $this->pluginName . '_' . $key;
        }
    }

    public function getReportsWithGoalMetrics(&$reportsWithGoals)
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');

        if ($idSite < 1) {
            return;
        }

        $dimensions = $this->configuration->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $dimension) {
            if (!$dimension['active']) {
                continue;
            }

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
        $dimensions = CustomDimensionsRequestProcessor::getCachedCustomDimensions($request);

        // we copy all visit custom dimensions, but only if the index also exists in the conversion table
        // to not fail while conversion custom dimensions are added
        $conversionIndexes = $this->getCachedInstalledIndexesForScope(self::SCOPE_CONVERSION);
        $conversionIndexes = array_map(function ($index) {
            return (int) $index; // make sure we work with integers
        }, $conversionIndexes);

        foreach ($dimensions as $dimension) {
            $index = (int) $dimension['index'];
            if ($dimension['scope'] === self::SCOPE_VISIT && in_array($index, $conversionIndexes)) {
                $field = LogTable::buildCustomDimensionColumnName($dimension);

                if (array_key_exists($field, $visitInformation)) {
                    $conversion[$field] = $visitInformation[$field];
                }
            }
        }
    }

    public function addVisitFieldsToPersist(&$fields)
    {
        $indexes = $this->getCachedInstalledIndexesForScope(self::SCOPE_VISIT);

        $fields[] = 'last_idlink_va';

        foreach ($indexes as $index) {
            $fields[] = LogTable::buildCustomDimensionColumnName($index);
        }
    }

    public function getCachedInstalledIndexesForScope($scope)
    {
        $cache = Cache::getCacheGeneral();
        $key = 'custom_dimension_indexes_installed_' . $scope;

        if (empty($cache[$key])) {
            return array();
        }

        return $cache[$key];
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
        return array(self::SCOPE_VISIT, self::SCOPE_ACTION, self::SCOPE_CONVERSION);
    }

    /**
     * These are public scopes that are actually visible to the user, scope Conversion
     * is not really directly visible to the user and a user cannot manage/configure dimensions in scope conversion.
     */
    public static function getPublicScopes()
    {
        return array(CustomDimensions::SCOPE_VISIT, CustomDimensions::SCOPE_ACTION);
    }
}
