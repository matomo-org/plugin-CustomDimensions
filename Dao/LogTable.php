<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions\Dao;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\Log;
use Piwik\Plugins\CustomDimensions\CustomDimensions;

class LogTable
{
    const DEFAULT_CUSTOM_DIMENSION_COUNT = 5;

    private $scope = null;
    private $table = null;

    public function __construct($scope)
    {
        if (empty($scope) || !in_array($scope, CustomDimensions::getScopes())) {
            throw new \Exception('Invalid custom dimension scope');
        }

        $this->scope = $scope;
        $this->table = Common::prefixTable($this->getTableNameFromScope($scope));
    }

    private function getTableNameFromScope($scope)
    {
        // actually we should have a class for each scope but don't want to overengineer it for now
        switch ($scope) {
            case CustomDimensions::SCOPE_ACTION:
                return 'log_link_visit_action';
            case CustomDimensions::SCOPE_VISIT:
                return 'log_visit';
            case CustomDimensions::SCOPE_CONVERSION:
                return 'log_conversion';
        }
    }

    /**
     * @see getHighestCustomDimensionIndex()
     * @return int
     */
    public function getNumInstalledIndexes()
    {
        $indexes = $this->getInstalledIndexes();

        return count($indexes);
    }

    public function getInstalledIndexes()
    {
        $columns = $this->getCustomDimensionColumnNames();

        if (empty($columns)) {
            return array();
        }

        $indexes = array_map(function ($column) {
            $onlyNumber = str_replace('custom_dimension_', '', $column);

            if (is_numeric($onlyNumber)) {
                return (int) $onlyNumber;
            }
        }, $columns);

        return array_values(array_unique($indexes));
    }

    private function getCustomDimensionColumnNames()
    {
        $columns = Db::getColumnNamesFromTable($this->table);

        $dimensionColumns = array_filter($columns, function ($column) {
            return LogTable::isCustomDimensionColumn($column);
        });

        return $dimensionColumns;
    }

    public static function isCustomDimensionColumn($column)
    {
        return preg_match('/^custom_dimension_(\d+)$/', $column);
    }

    public static function buildCustomDimensionColumnName($index)
    {
        return 'custom_dimension_' . (int) $index;
    }

    public function removeCustomDimension($index)
    {
        if ($index < 1) {
            return;
        }

        $field = self::buildCustomDimensionColumnName($index);

        $sql = sprintf('ALTER TABLE %s DROP COLUMN %s;', $this->table, $field);
        Db::exec($sql);
    }

    public function addManyCustomDimensions($count)
    {
        if ($count <= 0) {
            return;
        }

        $numDimensionsInstalled = $this->getNumInstalledIndexes();
        $total = $numDimensionsInstalled + $count;

        $queries = array();
        for ($index = $numDimensionsInstalled; $index < $total; $index++) {
            $queries[] = $this->getAddColumnQueryToAddCustomDimension($index + 1);
        }

        if (!empty($queries)) {
            $sql = 'ALTER TABLE ' . $this->table . ' ' . implode(', ', $queries) . ';';
            Db::exec($sql);
        }
    }

    private function getAddColumnQueryToAddCustomDimension($index)
    {
        $maxLen = CustomDimensions::getMaxLengthCustomDimensions();
        $field  = self::buildCustomDimensionColumnName($index);

        return sprintf('ADD COLUMN %s VARCHAR(%d) DEFAULT NULL', $field, $maxLen);
    }

    public function install()
    {
        try {
            $numDimensionsInstalled = $this->getNumInstalledIndexes();
            $numDimensionsToAdd = self::DEFAULT_CUSTOM_DIMENSION_COUNT - $numDimensionsInstalled;

            $this->addManyCustomDimensions($numDimensionsToAdd);

        } catch (\Exception $e) {
            Log::error('Failed to add custom dimension: ' . $e->getMessage());
        }
    }

    public function uninstall()
    {
        foreach ($this->getInstalledIndexes() as $index) {
            $this->removeCustomDimension($index);
        }
    }

}

