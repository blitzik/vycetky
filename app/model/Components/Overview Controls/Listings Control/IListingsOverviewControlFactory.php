<?php

namespace App\Model\Components;

interface IListingsOverviewControlFactory
{
    /**
     * @param array $filterParams
     * @return ListingsOverviewControl
     */
    public function create(array $filterParams);
}