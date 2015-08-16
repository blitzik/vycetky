<?php

namespace App\Model\Components\ItemsTable;

use App\Model\Entities\Listing;

interface IItemsTableControlFactory
{
    /**
     * @return ItemsTableControl
     */
    public function create(Listing $listing);
}