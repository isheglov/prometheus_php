<?php
/**
 * @file
 * Contains Bexio\PrometheusPHP\Tests\Storage\InMemory\HistogramTest.
 */

namespace Bexio\PrometheusPHP\Tests\Storage\InMemory;

use Bexio\PrometheusPHP\Metric\Counter;
use Bexio\PrometheusPHP\Metric\CounterCollection;
use Bexio\PrometheusPHP\Metric\Histogram;
use Bexio\PrometheusPHP\Sample;
use Bexio\PrometheusPHP\Storage\InMemory;

class HistogramTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InMemory
     */
    private $subject;

    protected function setUp()
    {
        $this->subject = new InMemory(array(
            'foo_bar_baz' => array(
                '{"le":1}' => 7,
                '{"le":10}' => 5,
                '{"le":"+Inf"}' => 3,
                '{"foo":"bar","le":1}' => 11,
                '{"foo":"bar","le":10}' => 7,
                '{"foo":"bar","le":"+Inf"}' => 5,
            ),
        ));
    }

    /**
     * @return array
     */
    public function getSingleData()
    {
        $defaultLowBucketResults = array(8, 8 + 5, 8 + 5 + 3);
        $defaultHighBucketResults = array(7, 7 + 6, 7 + 6 + 3);
        $defaultInfBucketResults = array(7, 7 + 5, 7 + 5 + 4);
        $labeledLowBucketResults = array(12, 12 + 7, 12 + 7 + 5);
        $labeledHighBucketResults = array(11, 11 + 8, 11 + 8 + 5);
        $labeledInfBucketResults = array(11, 11 + 7, 11 + 7 + 6);
        $nullLowBucketResults = array(1 , 1 , 1);
        $nullHighBucketResults = array(0 , 1 , 1);
        $nullInfBucketResults = array(0 , 0 , 1);

        return array(
            array(HistogramTest::getDefaultHistogram(), 0.001, $defaultLowBucketResults),
            array(HistogramTest::getDefaultHistogram(), 0.999, $defaultLowBucketResults),
            array(HistogramTest::getDefaultHistogram(), 1, $defaultLowBucketResults),
            array(HistogramTest::getDefaultHistogram(), 1.001, $defaultHighBucketResults),
            array(HistogramTest::getDefaultHistogram(), 9.999, $defaultHighBucketResults),
            array(HistogramTest::getDefaultHistogram(), 10, $defaultHighBucketResults),
            array(HistogramTest::getDefaultHistogram(), 11.001, $defaultInfBucketResults),
            array(HistogramTest::getDefaultHistogram(), 11, $defaultInfBucketResults),
            array(HistogramTest::getLabeledHistogram(), 0.001, $labeledLowBucketResults),
            array(HistogramTest::getLabeledHistogram(), 0.999, $labeledLowBucketResults),
            array(HistogramTest::getLabeledHistogram(), 1, $labeledLowBucketResults),
            array(HistogramTest::getLabeledHistogram(), 1.001, $labeledHighBucketResults),
            array(HistogramTest::getLabeledHistogram(), 9.999, $labeledHighBucketResults),
            array(HistogramTest::getLabeledHistogram(), 10, $labeledHighBucketResults),
            array(HistogramTest::getLabeledHistogram(), 11.001, $labeledInfBucketResults),
            array(HistogramTest::getLabeledHistogram(), 11, $labeledInfBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 0.001, $nullLowBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 0.999, $nullLowBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 1, $nullLowBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 1.001, $nullHighBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 9.999, $nullHighBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 10, $nullHighBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 11.001, $nullInfBucketResults),
            array(Histogram::createFromValues('foo', null, array(1, 10)), 11, $nullInfBucketResults),
        );
    }

    /**
     * @param Histogram $metric
     * @param float     $value    The observed value.
     * @param float[]   $expected The expected bucket values.
     *
     * @dataProvider getSingleData
     */
    public function testSingle(Histogram $metric, $value, array $expected)
    {
        $metric->observe($value);
        $this->subject->persist($metric);
        $samples = $this->subject->collectSamples($metric);
        $this->assertEquals(count($expected), count($samples));
        for ($i = 0; $i < count($expected); $i++) {
            $this->assertEquals($expected[$i], $samples[$i]->getValue());
        }
    }

    /**
     * @return Histogram
     */
    private static function getDefaultHistogram()
    {
        return Histogram::createFromValues('baz', null, array(1,10),'foo', 'bar');
    }

    /**
     * @return Histogram
     */
    private static function getLabeledHistogram()
    {
        return Histogram::createFromValues('baz', null, array(1,10),'foo', 'bar', array(
            'foo' => 'bar',
        ));
    }
}
