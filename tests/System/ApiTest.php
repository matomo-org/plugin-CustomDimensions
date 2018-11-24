<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomDimensions\tests\System;

use Piwik\Plugin\Manager;
use Piwik\Plugins\CustomDimensions\tests\Fixtures\TrackVisitsWithCustomDimensionsFixture;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Version;

/**
 * @group CustomDimensions
 * @group ApiTest
 * @group Plugins
 */
class ApiTest extends SystemTestCase
{
    /**
     * @var TrackVisitsWithCustomDimensionsFixture
     */
    public static $fixture = null; // initialized below class definition

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $api = array(
            'CustomDimensions.getCustomDimension',
        );

        $tests = array(
            array('idSite' => 1, 'idDimension' => 1),
            array('idSite' => 1, 'idDimension' => 2),
            array('idSite' => 1, 'idDimension' => 3),
            array('idSite' => 1, 'idDimension' => 4),
            array('idSite' => 1, 'idDimension' => 5),
            array('idSite' => 1, 'idDimension' => 6),
            array('idSite' => 2, 'idDimension' => 1),
            array('idSite' => 1, 'idDimension' => 999), // dimension does not exist
        );

        $hideColumns = 'sum_time_generation,sum_bandwidth,nb_hits_with_bandwidth,min_bandwidth,max_bandwidth,avg_bandwidth,nb_total_overall_bandwidth,nb_total_pageview_bandwidth,nb_total_download_bandwidth';

        $apiToTest = array();

        foreach ($tests as $test) {
            $idSite = $test['idSite'];
            $idDimension = $test['idDimension'];

            foreach (array('day', 'year') as $period) {
                $apiToTest[] = array($api,
                    array(
                        'idSite'     => $idSite,
                        'date'       => self::$fixture->dateTime,
                        'periods'    => array($period),
                        'otherRequestParameters' => array(
                            'idDimension' => $idDimension,
                            'expanded' => '0',
                            'flat' => '0',
                            'hideColumns' => $hideColumns
                        ),
                        'testSuffix' => "${period}_site_${idSite}_dimension_${idDimension}"
                    )
                );
            }

        }

        $apiToTest[] = array($api, array(
            'idSite'     => 1,
            'date'       => self::$fixture->dateTime,
            'periods'    => array('day'),
            'otherRequestParameters' => array(
                'idDimension' => 3,
                'expanded' => '1',
                'flat' => '0',
                'hideColumns' => $hideColumns
            ),
            'testSuffix' => "day_site_1_dimension_3_expanded"
        ));

        $apiToTest[] = array($api, array(
            'idSite'     => 1,
            'date'       => self::$fixture->dateTime,
            'periods'    => array('day'),
            'otherRequestParameters' => array(
                'idDimension' => 3,
                'expanded' => '0',
                'flat' => '1',
                'hideColumns' => $hideColumns
            ),
            'testSuffix' => "day_site_1_dimension_3_flat"
        ));

        $apiToTest[] = array($api,
            array(
                'idSite'     => 1,
                'date'       => self::$fixture->dateTime,
                'periods'    => array('year'),
                'segment'    => 'dimension1=@value5',
                'otherRequestParameters' => array(
                    'idDimension' => 1,
                    'hideColumns' => $hideColumns
                ),
                'testSuffix' => "year_site_1_dimension_1_withsegment"
            )
        );

        foreach (array(1, 2, 99) as $idSite) {
            $api = array('CustomDimensions.getConfiguredCustomDimensions',
                         'CustomDimensions.getAvailableScopes');
            $apiToTest[] = array($api,
                array(
                    'idSite'     => $idSite,
                    'date'       => self::$fixture->dateTime,
                    'periods'    => array('day'),
                    'testSuffix' => '_' . $idSite
                )
            );
        }

        $apiToTest[] = array(array('CustomDimensions.getAvailableExtractionDimensions'),
            array(
                'idSite'  => 1,
                'date'    => self::$fixture->dateTime,
                'periods' => array('day')
            )
        );

        $apiToTest[] = array(
            array('API.getReportMetadata'),
            array(
                'idSite' => 1,
                'date' => self::$fixture->dateTime,
                'periods' => array('day')
            )
        );

        $apiToTest[] = array(array('API.getSegmentsMetadata'),
            array(
                'idSite'  => 1,
                'date'    => self::$fixture->dateTime,
                'periods' => array('year'),
                'otherRequestParameters' => [
                    'hideColumns' => 'acceptedValues' // hide accepted values as they might change
                ]
            )
        );

        $apiToTest[] = array(array('API.getProcessedReport'),
                             array(
                                 'idSite'  => 1,
                                 'date'    => self::$fixture->dateTime,
                                 'periods' => array('year'),
                                 'otherRequestParameters' => array(
                                     'apiModule' => 'CustomDimensions',
                                     'apiAction' => 'getCustomDimension',
                                     'idDimension' => '3'
                                 ),
                                 'testSuffix' => '_actionDimension',
                                 'xmlFieldsToRemove' => ['idsubdatatable']
                             )
        );

        $apiToTest[] = array(array('API.getProcessedReport'),
            array(
                'idSite'  => 1,
                'date'    => self::$fixture->dateTime,
                'periods' => array('year'),
                'otherRequestParameters' => array(
                    'apiModule' => 'CustomDimensions',
                    'apiAction' => 'getCustomDimension',
                    'idDimension' => '1'
                ),
                'testSuffix' => '_visitDimension'
            )
        );

        if (version_compare(Version::VERSION, '3.6.1', '>=')) {
            $apiToTest[] = array(
                array('Live.getLastVisitsDetails'),
                array(
                    'idSite'                 => 1,
                    'date'                   => self::$fixture->dateTime,
                    'periods'                => array('year'),
                    'otherRequestParameters' => array(
                        'hideColumns' => 'generationTimeMilliseconds,totalEcommerceRevenue,totalEcommerceConversions,totalEcommerceItems,totalAbandonedCarts,totalAbandonedCartsRevenue,totalAbandonedCartsItems'
                    )
                )
            );
        }

        return $apiToTest;
    }

    public static function getOutputPrefix()
    {
        return '';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }

}

ApiTest::$fixture = new TrackVisitsWithCustomDimensionsFixture();