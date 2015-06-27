<?php

namespace App\Model\Components\ItemsTable;

interface IItemsTableControlFactory
{
    /**
     * @return ItemsTableControl
     */
    public function create(\DateTime $period);
}