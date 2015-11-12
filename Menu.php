<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions;

use Piwik\Common;
use Piwik\Menu\Group;
use Piwik\Menu\MenuReporting;
use Piwik\Menu\MenuUser;
use Piwik\Piwik;
use Piwik\Plugins\CustomDimensions\Dao\Configuration;
use Piwik\Plugins\UsersManager\UserPreferences;

/**
 * This class allows you to add, remove or rename menu items.
 * To configure a menu (such as Admin Menu, Reporting Menu, User Menu...) simply call the corresponding methods as
 * described in the API-Reference http://developer.piwik.org/api-reference/Piwik/Menu/MenuAbstract
 */
class Menu extends \Piwik\Plugin\Menu
{
    public function configureUserMenu(MenuUser $menu)
    {
        $userPreferences = new UserPreferences();
        $default = $userPreferences->getDefaultWebsiteId();
        $idSite = Common::getRequestVar('idSite', $default, 'int');

        if (Piwik::isUserHasAdminAccess($idSite)) {
            $menu->addManageItem('CustomDimensions_CustomDimensions', $this->urlForAction('manage'), $orderId = 16);
        }
    }

    public function configureReportingMenu(MenuReporting $menu)
    {
        $idSite = Common::getRequestVar('idSite', null, 'int');

        $config     = new Configuration();
        $dimensions = $config->getCustomDimensionsForSite($idSite);

        foreach ($dimensions as $index => $dimension) {
            if (!$dimension['active']) {
                unset($dimensions[$index]);
            }
        }

        $this->addMenuItemsForCustomDimensions($menu, $dimensions, CustomDimensions::SCOPE_VISIT);
        $this->addMenuItemsForCustomDimensions($menu, $dimensions, CustomDimensions::SCOPE_ACTION);
    }

    private function addMenuItemsForCustomDimensions(MenuReporting $menu, $dimensions, $scope)
    {
        $numItems = 0;

        foreach ($dimensions as $dimension) {
            if ($scope === $dimension['scope']) {
                $numItems++;
            }
        }

        $group = new Group();
        $mainMenuName = '';
        if ($scope === CustomDimensions::SCOPE_VISIT) {
            $mainMenuName = 'General_Visitors';
        } elseif ($scope === CustomDimensions::SCOPE_ACTION) {
            $mainMenuName = 'General_Actions';
        }

        foreach ($dimensions as $dimension) {
            if ($dimension['scope'] !== $scope) {
                continue;
            }

            $name  = $dimension['name'];
            $id    = $dimension['idcustomdimension'];
            $url   = $this->urlForAction('menuGetCustomDimension', array('idDimension' => $id));
            $order = 100 + $id;
            $tooltip = Piwik::translate('CustomDimensions_CustomDimensionId', $id);

            if ($scope === CustomDimensions::SCOPE_VISIT) {

                if ($numItems > 3) {
                    $group->add($name, $url, $tooltip);
                } else {
                    $menu->addVisitorsItem($name, $url, $order, $tooltip);
                }
            } elseif ($scope === CustomDimensions::SCOPE_ACTION) {
                if ($numItems > 3) {
                    $group->add($name, $url, $tooltip);
                } else {
                    $menu->addActionsItem($name, $url, $order, $tooltip);
                }
            }

            if ($numItems > 3) {
                $title = Piwik::translate('CustomDimensions_CustomDimensions');
                $menu->addGroup($mainMenuName, $title, $group, ++$order, $tooltip = false);
            }
        }
    }


}
