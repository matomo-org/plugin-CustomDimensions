<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\DataTable\Row;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Piwik;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Plugins\CustomDimensions\Dimension\Active;
use Piwik\Plugins\CustomDimensions\Dimension\Dimension;
use Piwik\Plugins\CustomDimensions\Dimension\Extraction;
use Piwik\Plugins\CustomDimensions\Dimension\Extractions;
use Piwik\Plugins\CustomDimensions\Dimension\Index;
use Piwik\Plugins\CustomDimensions\Dimension\Name;
use Piwik\Plugins\CustomDimensions\Dimension\Scope;
use Piwik\Tracker\Cache;

/**
 * The Custom Dimensions API lets you manage and access reports for your
 * <a href='http://piwik.org/docs/custom-dimensions/' rel='noreferrer' target='_blank'>Custom Dimensions</a>.
 *
 * @method static API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    public function getCustomDimension($idDimension, $idSite, $period, $date, $segment = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dimension = new Dimension($idDimension, $idSite);
        $dimension->checkActive();

        $record = Archiver::buildRecordNameForCustomDimensionId($idDimension);

        $dataTable = Archive::createDataTableFromArchive($record, $idSite, $period, $date, $segment);
        $dataTable->filter('Piwik\Plugins\CustomDimensions\DataTable\Filter\RemoveUserIfNeeded', array($idSite, $period, $date));
        $dataTable->queueFilter('Piwik\Plugins\CustomDimensions\DataTable\Filter\AddSegmentMetadata', array($idDimension));

        return $dataTable;
    }

    public function getConfiguredCustomDimensions($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $configs = $this->getConfiguration()->getCustomDimensionsForSite($idSite);

        return $configs;
    }

    public function configureNewCustomDimension($idSite, $name, $scope, $active, $extractions = array())
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $this->validateCustomDimensionConfig($name, $active, $extractions);

        $scopeCheck = new Scope($scope);
        $scopeCheck->check();

        $index = new Index();
        $index = $index->getNextIndex($idSite, $scope);

        $configuration = $this->getConfiguration();
        $idDimension   = $configuration->configureNewDimension($idSite, $name, $scope, $index, $active, $extractions);

        Cache::clearWebsiteCache($idSite);
        Cache::clearCacheGeneral();

        return $idDimension;
    }

    public function configureExistingCustomDimension($idDimension, $idSite, $name, $active, $extractions = array())
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $dimension = new Dimension($idDimension, $idSite);
        $dimension->checkExists();

        $this->validateCustomDimensionConfig($name, $active, $extractions);

        $this->getConfiguration()->configureExistingDimension($idDimension, $idSite, $name, $active, $extractions);

        Cache::clearWebsiteCache($idSite);
        Cache::clearCacheGeneral();
    }

    private function validateCustomDimensionConfig($name, $active, $extractions)
    {
        // ideally we would work with these objects a bit more instead of arrays but we'd have a lot of
        // serialize/unserialize to do as we need to cache all configured custom dimensions for tracker cache and
        // we do not want to serialize all php instances there. Also we need to return an array for each
        // configured dimension in API methods anyway

        $name = new Name($name);
        $name->check();

        $active = new Active($active);
        $active->check();

        $extractions = new Extractions($extractions);
        $extractions->check();
    }

    public function getAvailableScopes($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $scopes = array();
        foreach (CustomDimensions::getPublicScopes() as $scope) {

            $configs = $this->getConfiguration()->getCustomDimensionsHavingScope($idSite, $scope);
            $indexes = $this->getTracking($scope)->getInstalledIndexes();

            $scopes[] = array(
                'name' => $scope,
                'numSlotsAvailable' => count($indexes),
                'numSlotsUsed' => count($configs),
                'numSlotsLeft' => count($indexes) - count($configs)
            );
        }

        return $scopes;
    }

    public function getAvailableExtractionDimensions()
    {
        Piwik::checkUserHasSomeAdminAccess();

        $supported = Extraction::getSupportedDimensions();

        $dimensions = array();
        foreach ($supported as $value => $dimension) {
            $dimensions[] = array('value' => $value, 'name' => $dimension);
        }

        return $dimensions;
    }

    private function getTracking($scope)
    {
        return new LogTable($scope);
    }

    private function getConfiguration()
    {
        return new Configuration();
    }

}

