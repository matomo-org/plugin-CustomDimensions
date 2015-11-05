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
use Piwik\Db;
use Piwik\DbHelper;

class Configuration
{
    /**
     * @var Db
     */
    private $db;

    private $tableName = 'custom_dimensions';
    private $tableNamePrefixed;

    public function __construct()
    {
        $this->db = Db::get();
        $this->tableNamePrefixed = Common::prefixTable($this->tableName);
    }

    public function getCustomDimensionsForSite($idSite)
    {
        $configs = $this->db->fetchAll("SELECT * FROM " . $this->tableNamePrefixed . " WHERE idsite = ?", $idSite);

        foreach ($configs as $index => $config) {
            $configs[$index]['extractions'] = $this->decodeExtractions($configs[$index]['extractions']);
            $configs[$index]['active'] = (bool) $configs[$index]['active'];
        }

        return $configs;
    }

    public function getCustomDimension($idSite, $idCustomDimension)
    {
        $query = "SELECT * FROM " . $this->tableNamePrefixed . " WHERE idcustomdimension = ? and idsite = ?";
        $config = $this->db->fetchRow($query, array($idCustomDimension, $idSite));

        $config['extractions'] = $this->decodeExtractions($config['extractions']);
        $config['active'] = (bool) $config['active'];

        return $config;
    }

    public function getCustomDimensionsHavingScope($idSite, $scope)
    {
        $query= "SELECT * FROM " . $this->tableNamePrefixed . " WHERE idsite = ? and scope = ?";
        $configs = $this->db->fetchAll($query, array($idSite, $scope));

        return $configs;
    }

    public function getCustomDimensionsHavingIndex($scope, $index)
    {
        $query= "SELECT * FROM " . $this->tableNamePrefixed . " WHERE `index` = ? and scope = ?";
        $configs = $this->db->fetchAll($query, array($index, $scope));

        return $configs;
    }

    public function deleteConfigurationsForSite($idSite)
    {
        $this->db->query("DELETE FROM " . $this->tableNamePrefixed . " WHERE idsite = ?", $idSite);
    }

    public function deleteConfigurationsForIndex($index)
    {
        $this->db->query("DELETE FROM " . $this->tableNamePrefixed . " WHERE `index` = ?", $index);
    }

    private function getNextCustomDimensionIdForSite($idSite)
    {
        $nextId = $this->db->fetchOne("SELECT max(idcustomdimension) FROM " . $this->tableNamePrefixed . " WHERE idsite = ?", $idSite);

        if (empty($nextId)) {
            $nextId = 1;
        } else {
            $nextId = (int) $nextId + 1;
        }

        return $nextId;
    }

    public function configureNewDimension($idSite, $name, $scope, $index, $active, $extractions)
    {
        $extractions = $this->encodeExtractions($extractions);
        $active = $active ? '1' : '0';
        $id = $this->getNextCustomDimensionIdForSite($idSite);

        $config = array(
            'idcustomdimension' => $id,
            'idsite'      => $idSite,
            'index'       => $index,
            'scope'       => $scope,
            'name'        => $name,
            'active'      => $active,
            'extractions' => $extractions,
        );

        $this->db->insert($this->tableNamePrefixed, $config);

        $idDimension = $this->db->lastInsertId();

        return $idDimension;
    }

    public function configureExistingDimension($idCustomDimension, $idSite, $name, $active, $extractions)
    {
        $extractions = $this->encodeExtractions($extractions);
        $active = $active ? '1' : '0';

        $this->db->update($this->tableNamePrefixed,
            array(
                'name'        => $name,
                'active'      => $active,
                'extractions' => $extractions,
            ),
            "idcustomdimension = " . (int) $idCustomDimension . " and idsite = " . (int) $idSite
        );
    }

    public function install()
    {
        $table = "`idcustomdimension` BIGINT UNSIGNED NOT NULL,
                  `idsite` BIGINT UNSIGNED NOT NULL ,
                  `name` VARCHAR(100) NOT NULL ,
                  `index` SMALLINT UNSIGNED NOT NULL ,
                  `scope` VARCHAR(10) NOT NULL ,
                  `active` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                  `extractions` TEXT NOT NULL DEFAULT '',
                  UNIQUE KEY idcustomdimension_idsite (`idcustomdimension`, `idsite`),
                  UNIQUE KEY uniq_hash(idsite, `scope`, `index`)";

        DbHelper::createTable($this->tableName, $table);
    }

    public function uninstall()
    {
        Db::dropTables(array($this->tableNamePrefixed));
    }

    private function encodeExtractions($extractions)
    {
        return json_encode($extractions);
    }

    private function decodeExtractions($extractions)
    {
        if (!empty($extractions)) {
            $extractions = json_decode($extractions, true);
        }

        if (empty($extractions) || !is_array($extractions)) {
            $extractions = array();
        }

        return $extractions;
    }

}