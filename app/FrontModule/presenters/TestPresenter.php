<?php

namespace App\FrontModule\Presenters;

use App\Model\Entities\Invitation;
use App\Model\Entities\Listing;
use App\Model\Entities\ListingItem;
use App\Model\Entities\WorkedHours;
use App\Model\Facades\ItemFacade;
use App\Model\Repositories\ListingItemRepository;
use App\Model\Repositories\ListingRepository;
use App\Model\Repositories\LocalityRepository;
use App\Model\Repositories\WorkedHoursRepository;
use LeanMapper\Connection;
use Nette\Utils\DateTime;
use Nette\Utils\Validators;

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
        dump(filter_var('0:az0::0', FILTER_VALIDATE_IP, ['flags' => [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6]]));
    }

    public function renderDefault()
    {

    }
}
