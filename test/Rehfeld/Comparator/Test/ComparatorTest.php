<?php

namespace Rehfeld\Comparator\Test;

use Rehfeld\Comparator\Comparator;
use Rehfeld\Comparator\ComparatorBuilder;

class ComparatorTest extends \PHPUnit_Framework_TestCase
{
    function testSortByValueExtractor()
    {
        // Compare by price
        $valueExtractor = function(ComparatorTestFood $obj) {
            return $obj->getPrice();
        };

        $a = new ComparatorTestFood('', 1);
        $b = new ComparatorTestFood('', 5);

        // Asc comparator
        $ascComparator = Comparator::sortAscByValueExtractor($valueExtractor);
        $this->assertThreeScenarios($ascComparator, $a, $b);

        // Desc comparator
        $descComparator = Comparator::sortDescByValueExtractor($valueExtractor);
        $this->assertThreeScenarios($descComparator, $b, $a);

        // Make sure the alternate interface produces equal results
        $altAscComparator = Comparator::sortByValueExtractor($valueExtractor, true);
        $this->assertBothComparatorsProduceEqualResults($ascComparator, $altAscComparator, $a, $b);

        $altDescComparator = Comparator::sortByValueExtractor($valueExtractor, false);
        $this->assertBothComparatorsProduceEqualResults($descComparator, $altDescComparator, $a, $b);
    }

    function testSortByMethod()
    {
        $a = new ComparatorTestFood('', 1);
        $b = new ComparatorTestFood('', 5);

        // Asc comparator
        $ascComparator = Comparator::sortAscByMethod('getPrice');
        $this->assertThreeScenarios($ascComparator, $a, $b);

        // Desc comparator
        $descComparator = Comparator::sortDescByMethod('getPrice');
        $this->assertThreeScenarios($descComparator, $b, $a);

        // Make sure the alternate interface produces equal results
        $altAscComparator = Comparator::sortByMethod('getPrice', true);
        $this->assertBothComparatorsProduceEqualResults($ascComparator, $altAscComparator, $a, $b);

        $altDescComparator = Comparator::sortByMethod('getPrice', false);
        $this->assertBothComparatorsProduceEqualResults($descComparator, $altDescComparator, $a, $b);
    }

    function testSortByProperty()
    {
        $a = new ComparatorTestFood('a', 0);
        $b = new ComparatorTestFood('b', 0);

        // Asc comparator
        $ascComparator = Comparator::sortAscByProperty('name');
        $this->assertThreeScenarios($ascComparator, $a, $b);

        // Desc comparator
        $descComparator = Comparator::sortDescByProperty('name');
        $this->assertThreeScenarios($descComparator, $b, $a);

        // Make sure the alternate interface produces equal results
        $altAscComparator = Comparator::sortByProperty('name', true);
        $this->assertBothComparatorsProduceEqualResults($ascComparator, $altAscComparator, $a, $b);

        $altDescComparator = Comparator::sortByProperty('name', false);
        $this->assertBothComparatorsProduceEqualResults($descComparator, $altDescComparator, $a, $b);
    }

    function testSortByArrayKey()
    {
        $a = array('age' => 1);
        $b = array('age' => 2);

        // Asc comparator
        $ascComparator = Comparator::sortAscByArrayKey('age');
        $this->assertThreeScenarios($ascComparator, $a, $b);

        // Desc comparator
        $descComparator = Comparator::sortDescByArrayKey('age');
        $this->assertThreeScenarios($descComparator, $b, $a);

        // Make sure the alternate interface produces equal results
        $altAscComparator = Comparator::sortByArrayKey('age', true);
        $this->assertBothComparatorsProduceEqualResults($ascComparator, $altAscComparator, $a, $b);

        $altDescComparator = Comparator::sortByArrayKey('age', false);
        $this->assertBothComparatorsProduceEqualResults($descComparator, $altDescComparator, $a, $b);
    }

    function testTieBreaker()
    {
        $food = array(
            new ComparatorTestFood('bacon',    4.99),
            new ComparatorTestFood('nutella',  9.99),
            new ComparatorTestFood('nutella',  9.99),
            new ComparatorTestFood('butter',   2.99),
            new ComparatorTestFood('chips',    4.99),
            new ComparatorTestFood('apple',    4.99),
        );

        usort($food, Comparator::tieBreaker(
            Comparator::sortAscByMethod('getPrice'),
            Comparator::sortDescByProperty('name')
        ));

        // Sorted by price asc, then by name desc
        $expected = array(
            new ComparatorTestFood('butter',   2.99),
            new ComparatorTestFood('chips',    4.99),
            new ComparatorTestFood('bacon',    4.99),
            new ComparatorTestFood('apple',    4.99),
            new ComparatorTestFood('nutella',  9.99),
            new ComparatorTestFood('nutella',  9.99),
        );

        $this->assertEquals($expected, $food);
    }

    function testComparatorBuilderIdentityFunction()
    {
        $arr = array(1, 2, 3);
        usort($arr, ComparatorBuilder::create()->setSortAscending(false)->build());
        $this->assertEquals(array(3, 2, 1), $arr);
    }

    function testComparatorBuilderValueComparatorFunction()
    {
        $arr = array('a', 'b', 'B', 'c');
        usort($arr, ComparatorBuilder::create()->setValueComparator('strcasecmp')->build());
        $this->assertEquals(array('a', 'B', 'b', 'c'), $arr);
    }

    function assertBothComparatorsProduceEqualResults(callable $comparatorA, callable $comparatorB, $valueToCompareA, $valueToCompareB)
    {
        $this->assertEquals($comparatorA($valueToCompareA, $valueToCompareB), $comparatorB($valueToCompareA, $valueToCompareB));
        $this->assertEquals($comparatorA($valueToCompareB, $valueToCompareA), $comparatorB($valueToCompareB, $valueToCompareA));
        $this->assertEquals($comparatorA($valueToCompareA, $valueToCompareA), $comparatorB($valueToCompareA, $valueToCompareA));
    }

    /**
     * @param callable $comparator
     * @param          $lowerValue - when a comparator which sorts ascending is used, this arg will be considered the "lower" value by the comparator.
     * @param          $higherValue
     */
    function assertThreeScenarios(callable $comparator, $lowerValue, $higherValue)
    {
        $this->assertEquals(-1, $comparator($lowerValue, $higherValue));
        $this->assertEquals(1,  $comparator($higherValue, $lowerValue));
        $this->assertEquals(0,  $comparator($lowerValue, $lowerValue));
        $this->assertEquals(0,  $comparator($higherValue, $higherValue));
    }
}
