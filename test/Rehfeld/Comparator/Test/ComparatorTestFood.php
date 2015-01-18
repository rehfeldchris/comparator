<?php

namespace Rehfeld\Comparator\Test;

class ComparatorTestFood
{
    /** @type string */
    public $name;

    /** @type float */
    private $price;

    /**
     * @param string $name
     * @param float $price
     */
    function __construct($name, $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
}