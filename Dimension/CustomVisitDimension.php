<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions\Dimension;

use Piwik\Plugin\Dimension\VisitDimension;

class CustomVisitDimension extends VisitDimension
{
    public function __construct($column, $name)
    {
        $this->columnName = $column;
        $this->actualName = $name;
    }

    /**
     * The name of the dimension which will be visible for instance in the UI of a related report and in the mobile app.
     * @return string
     */
    public function getName()
    {
        return $this->actualName;
    }

}