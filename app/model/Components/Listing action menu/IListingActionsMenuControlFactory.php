<?php

namespace App\Model\Components;

use App\Model\Entities\Listing;

interface IListingActionsMenuControlFactory
{
    /**
     * @param Listing $listing
     * @return ListingActionsMenuControl
     */
    public function create(Listing $listing);
}