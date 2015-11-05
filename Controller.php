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
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    public function manage()
    {
        $idSite = Common::getRequestVar('idSite');

        Piwik::checkUserHasAdminAccess($idSite);

        return $this->renderTemplate('manage', array());
    }

    public function menuGetCustomDimension()
    {
        $dimension = $this->getDimensionIfValid();

        return View::singleReport($dimension['name'], $this->getCustomDimension());
    }

    public function getCustomDimension()
    {
        $this->getDimensionIfValid();

        return $this->renderReport('getCustomDimension', 'getCustomDimension');
    }

    private function getDimensionIfValid()
    {
        $idSite = Common::getRequestVar('idSite', null, 'int');
        Piwik::checkUserHasViewAccess($idSite);

        $idDimension = Common::getRequestVar('idDimension', 0, 'int');
        $config = new Configuration();
        $dimension = $config->getCustomDimension($idSite, $idDimension);

        if (empty($dimension)) {
            throw new \Exception("Dimension $idDimension does not exist for site $idSite");
        }

        return $dimension;
    }

}

