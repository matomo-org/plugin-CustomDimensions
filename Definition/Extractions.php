<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomDimensions\Definition;

class Extractions
{
    /**
     * @var Extraction[]
     */
    private $extractions = array();

    public function addExtraction(Extraction $extraction)
    {
        $this->extractions[] = $extraction;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $e = array();

        foreach ($this->extractions as $extraction) {
            $e[] = array(
                'dimension' => $extraction->getDimension(),
                'pattern'   => $extraction->getPattern()
            );
        }

        return json_encode($e);
    }

    public function fromString($extractions)
    {
        if (!empty($extractions)) {
            $extractions = json_decode($extractions, true);
        }

        if (empty($extractions) || !is_array($extractions)) {
            $extractions = array();
        }

        foreach ($extractions as $extraction) {
            $this->extractions[] = new Extraction($extraction['dimension'], $extraction['pattern']);
        }

        return $this->extractions;
    }

}