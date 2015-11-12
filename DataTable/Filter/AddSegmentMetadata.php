<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomDimensions\DataTable\Filter;

use Piwik\DataTable\BaseFilter;
use Piwik\DataTable\Row;
use Piwik\DataTable;
use Piwik\Plugins\CustomDimensions\Tracker\CustomDimensionsRequestProcessor;

class AddSegmentMetadata extends BaseFilter
{
    private $idDimension;

    /**
     * Constructor.
     *
     * @param DataTable $table The table to eventually filter.
     */
    public function __construct($table, $idDimension)
    {
        parent::__construct($table);
        $this->idDimension = $idDimension;
    }

    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $dimension = CustomDimensionsRequestProcessor::buildCustomDimensionTrackingApiName($this->idDimension);

        foreach ($table->getRows() as $row) {
            $label = $row->getColumn('label');
            if ($label !== false) {
                $row->setMetadata('segment', $dimension . '==' . urlencode($label));
            }
        }
    }
}