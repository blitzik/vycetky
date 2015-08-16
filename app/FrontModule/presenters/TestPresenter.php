<?php

namespace App\FrontModule\Presenters;

use App\Model\Repositories\WorkedHoursRepository;
use App\Model\Repositories\ListingItemRepository;
use App\Model\Repositories\LocalityRepository;
use App\Model\Repositories\ListingRepository;
use App\Model\Facades\ItemFacade;
use LeanMapper\Connection;

class TestPresenter extends SecurityPresenter
{
    /**
     * @var ListingRepository
     * @inject
     */
    public $listingRepository;

    /**
     * @var ListingItemRepository
     * @inject
     */
    public $itemRepository;

    /**
     * @var ItemFacade
     * @inject
     */
    public $itemFacade;

    /**
     * @var Connection
     * @inject
     */
    public $conn;

    /**
     * @var WorkedHoursRepository
     * @inject
     */
    public $whRepos;

    /**
     * @var LocalityRepository
     * @inject
     */
    public $locRepos;

    public function actionDefault()
    {

    }

    public function renderDefault()
    {

    }
}
