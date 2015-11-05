<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomDimensions\Definition;

class Extraction
{
    private $dimension = '';
    private $pattern = '';

    public function __construct($dimension, $pattern)
    {
        $this->checkDimension($dimension);

        $this->dimension = $dimension;
        $this->pattern   = $pattern;
    }

    private function checkDimension($dimension)
    {
        if (!in_array($dimension, array('url', 'urlparam', 'action_name'))) {
            throw new \Exception('Unsupported dimension');
        }
    }

    public function getDimension()
    {
        return $this->dimension;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

}