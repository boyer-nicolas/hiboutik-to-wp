<?php

namespace Niwee\Niwhiboutik;

class Stock
{
    private $stocks;
    private $hiboutik;

    public function __construct()
    {
        $this->hiboutik = new Hiboutik();
    }

    public function setAll()
    {
        $this->stocks = $this->hiboutik->get_all_stocks();
    }

    public function getAll()
    {
        return $this->stocks;
    }
}