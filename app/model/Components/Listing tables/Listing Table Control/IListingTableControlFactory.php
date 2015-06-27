<?php

namespace App\Model\Components\ListingTable;

use App\Model\Entities\Listing;

interface IListingTableControlFactory
{
    /**
     * @param $listing
     * @return ListingTableControl
     */
    public function create(Listing $listing);
}