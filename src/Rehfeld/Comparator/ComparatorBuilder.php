<?php

namespace Rehfeld\Comparator;


class ComparatorBuilder
{
    /** @type callable */
    private $valueComparator;

    /** @type callable */
    private $valueExtractor;

    /** @type int */
    private $inversionFactor = 1;

    function __construct(callable $valueComparator = null, callable $valueExtractor = null)
    {
        $this->valueComparator = $valueComparator ? $valueComparator : array(__CLASS__, 'looseComparisonOperatorCompare');
        $this->valueExtractor = $valueExtractor   ? $valueExtractor  : array(__CLASS__, 'identity');
    }

    public static function create()
    {
        return new self();
    }

    private static function looseComparisonOperatorCompare($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        return $a < $b ? -1 : 1;
    }

    private static function identity($value)
    {
        return $value;
    }

    public function setValueExtractor(callable $valueExtractor)
    {
        $this->valueExtractor = $valueExtractor;
        return $this;
    }

    public function setValueComparator(callable $valueComparator)
    {
        $this->valueComparator = $valueComparator;
        return $this;
    }

    public function setSortAscending($isAscending)
    {
        $this->inversionFactor = $isAscending ? 1 : -1;
        return $this;
    }

    public function build()
    {
        $inversionFactor = $this->inversionFactor;
        $valueExtractor = $this->valueExtractor;
        $valueComparator = $this->valueComparator;
        return function($a, $b) use($valueExtractor, $valueComparator, $inversionFactor) {
            return $valueComparator($valueExtractor($a), $valueExtractor($b)) * $inversionFactor;
        };
    }

}