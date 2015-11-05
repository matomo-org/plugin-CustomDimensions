<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions;

use Piwik\Common;
use Piwik\Config;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataArray;
use Piwik\Metrics;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\CustomDimensions\Dao\LogTable;
use Piwik\Tracker\GoalManager;
use Piwik\Tracker;
use Piwik\ArchiveProcessor;

require_once PIWIK_INCLUDE_PATH . '/libs/PiwikTracker/PiwikTracker.php';

class Archiver extends \Piwik\Plugin\Archiver
{
    const LABEL_CUSTOM_VALUE_NOT_DEFINED = "Value not defined";
    private $recordNames = array();

    /**
     * @var DataArray
     */
    protected $dataArray;
    protected $maximumRowsInDataTableLevelZero;
    protected $maximumRowsInSubDataTable;
    protected $newEmptyRow;

    /**
     * @var ArchiveProcessor
     */
    private $processor;

    function __construct($processor)
    {
        parent::__construct($processor);

        $this->processor = $processor;

        $this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_custom_variables'];
        $this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_custom_variables'];
    }

    public static function buildRecordNameForCustomDimensionId($id)
    {
        return 'CustomDimensions_Dimension' . (int) $id;
    }

    private function getRecordNames()
    {
        if (!empty($this->recordNames)) {
            return $this->recordNames;
        }

        $dimensions = $this->getCustomDimensions();

        foreach ($dimensions as $dimension) {
            $this->recordNames[] = self::buildRecordNameForCustomDimensionId($dimension['idcustomdimension']);
        }

        return $this->recordNames;
    }

    private function getCustomDimensions()
    {
        $idSite = $this->processor->getParams()->getSite()->getId();
        $config = new Configuration();
        $dimensions = $config->getCustomDimensionsForSite($idSite);

        return $dimensions;
    }

    public function aggregateMultipleReports()
    {
        $columnsAggregationOperation = null;

        $this->getProcessor()->aggregateDataTableRecords(
            $this->getRecordNames(),
            $this->maximumRowsInDataTableLevelZero,
            $this->maximumRowsInSubDataTable,
            $columnToSort = Metrics::INDEX_NB_VISITS,
            $columnsAggregationOperation,
            $columnsToRenameAfterAggregation = null,
            $countRowsRecursive = array());
    }

    public function aggregateDayReport()
    {
        $dimensions = $this->getCustomDimensions();
        foreach ($dimensions as $dimension) {
            $this->dataArray = new DataArray();

            if ($dimension['scope'] === CustomDimensions::SCOPE_VISIT) {
                $this->aggregateFromVisits($dimension);
                $this->aggregateFromConversions($dimension);
            } elseif ($dimension['scope'] === CustomDimensions::SCOPE_ACTION) {
                $this->aggregateFromActions($dimension);
            }

            $this->dataArray->enrichMetricsWithConversions();
            $table = $this->dataArray->asDataTable();

            $blob = $table->getSerialized(
                $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
                $columnToSort = Metrics::INDEX_NB_VISITS
            );

            $recordName = self::buildRecordNameForCustomDimensionId($dimension['idcustomdimension']);
            $this->getProcessor()->insertBlobRecord($recordName, $blob);
        }
    }

    protected function aggregateFromVisits($dimension)
    {
        $valueField = LogTable::buildCustomDimensionColumnName($dimension['index']);

        $dimensions = array($valueField);
        $where = "%s.$valueField != ''";
        $query = $this->getLogAggregator()->queryVisitsByDimension($dimensions, $where);

        while ($row = $query->fetch()) {
            $value = $this->cleanCustomVarValue($row[$valueField]);

            $this->dataArray->sumMetricsVisits($value, $row);
        }
    }

    protected function aggregateFromConversions($dimension)
    {
        $valueField = LogTable::buildCustomDimensionColumnName($dimension['index']);

        $dimensions = array($valueField);
        $where = "%s.$valueField != ''";
        $query = $this->getLogAggregator()->queryConversionsByDimension($dimensions, $where);

        while ($row = $query->fetch()) {
            $value = $this->cleanCustomVarValue($row[$valueField]);

            $this->dataArray->sumMetricsGoals($value, $row);
        }
    }

    protected function aggregateFromActions($dimension)
    {
        $valueField = LogTable::buildCustomDimensionColumnName($dimension['index']);

        $dimensions = array($valueField);
        $where = "%s.$valueField != ''";

        $query = $this->getLogAggregator()->queryActionsByDimension($dimensions, $where);

        while ($row = $query->fetch()) {
            $value = $this->cleanCustomVarValue($row[$valueField]);

            $this->dataArray->sumMetricsActions($value, $row);
        }
    }

    protected function cleanCustomVarValue($value)
    {
        if (strlen($value)) {
            return $value;
        }

        return self::LABEL_CUSTOM_VALUE_NOT_DEFINED;
    }

}
