<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Piwik;
use Exception;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;

/**
 * The Custom Dimensions API lets you manage and access reports for your
 * <a href='http://piwik.org/docs/custom-dimensions/' rel='noreferrer' target='_blank'>Custom Dimensions</a>.
 *
 * @method static \Piwik\Plugins\CustomDimensions\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    public function getCustomDimension($idDimension, $idSite, $period, $date, $segment = false)
    {
        $record = Archiver::buildRecordNameForCustomDimensionId($idDimension);

        $dataTable = Archive::createDataTableFromArchive($record, $idSite, $period, $date, $segment);
        $dataTable->queueFilter('ColumnDelete', 'nb_uniq_visitors');

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

        $configuration = $this->getConfiguration();

        $indexes = $this->getTracking($scope)->getInstalledIndexes();
        $configs = $configuration->getCustomDimensionsHavingScope($idSite, $scope);
        foreach ($configs as $config) {
            $key = array_search($config['index'], $indexes);
            if ($key !== false) {
                unset($indexes[$key]);
            }
        }

        if (empty($indexes)) {
            throw new Exception('No slot left to create a new custom dimension. To create a new slot execute the command ...');
        }

        $index = array_shift($indexes);

        $configs = $configuration->configureNewDimension($idSite, $name, $scope, $index, $active, $extractions);

        return $configs;
    }

    private function validateCustomDimensionConfig($name, $active, $extractions)
    {
        if (!preg_match('/[A-Za-z\s\d-_]{1,255}/', $name)) {
            throw new Exception('Invalid Name');
        }

        if (!is_array($extractions)) {
            throw new Exception('Extractions has to be an array');
        }

        foreach ($extractions as $extraction) {
            if (!is_array($extraction)) {
                throw new Exception('Each extraction within extractions has to be an array');
            }

            if (count($extraction) !== 2 || !array_key_exists('dimension', $extraction) || !array_key_exists('pattern', $extraction)) {
                throw new Exception('Each extraction within extractions must have a key dimension and pattern only');
            }
        }

        if (!is_bool($active) && !in_array($active, array('0', '1'))) {
            throw new Exception('active has to be a 0 or 1');
        }
    }

    public function configureExistingCustomDimension($idCustomDimension, $idSite, $name, $active, $extractions = array())
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $this->validateCustomDimensionConfig($name, $active, $extractions);

        $config   = $this->getConfiguration();
        $existing = $config->getCustomDimension($idSite, $idCustomDimension);

        if (empty($existing)) {
            throw new Exception('Custom Dimension does not exist');
        }

        $this->getConfiguration()->configureExistingDimension($idCustomDimension, $idSite, $name, $active, $extractions);
    }

    public function getAvailableScopes($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $scopes = array();
        foreach (array(CustomDimensions::SCOPE_VISIT, CustomDimensions::SCOPE_ACTION) as $scope) {

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

    private function getTracking($scope)
    {
        return new LogTable($scope);
    }

    private function getConfiguration()
    {
        return new Configuration();
    }
}

