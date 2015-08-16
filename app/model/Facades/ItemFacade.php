<?php

namespace App\Model\Facades;

use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\DayExceedCurrentMonthException;
use Exceptions\Runtime\ListingItemNotFoundException;
use App\Model\Repositories\WorkedHoursRepository;
use App\Model\Repositories\ListingItemRepository;
use App\Model\Repositories\LocalityRepository;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Entities\WorkedHours;
use App\Model\Entities\ListingItem;
use App\Model\Services\ItemService;
use App\Model\Entities\Locality;
use App\Model\Entities\Listing;
use Nette\Utils\Validators;
use Nette\Security\User;
use Tracy\Debugger;

class ItemFacade extends BaseFacade
{

    /**
     * @var ListingItemRepository
     */
    private $listingItemRepository;

    /**
     * @var WorkedHoursRepository
     */
    private $workedHoursRepository;

    /**
     * @var LocalityRepository
     */
    private $localityRepository;

    /**
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var ItemService
     */
    private $itemService;

    public function __construct(
        ListingItemRepository $listingItemRepository,
        WorkedHoursRepository $workedHoursRepository,
        LocalityRepository $localityRepository,
        \Transaction $transaction,
        ItemService $itemService,
        User $user
    ) {
        parent::__construct($user);

        $this->listingItemRepository = $listingItemRepository;
        $this->workedHoursRepository = $workedHoursRepository;
        $this->localityRepository = $localityRepository;
        $this->transaction = $transaction;
        $this->itemService = $itemService;
    }

    /**
     * @param ListingItem $listingItem
     * @return ListingItem
     * @throws ListingItemDayAlreadyExistsException
     * @throws \DibiException
     */
    public function saveListingItem(ListingItem $listingItem)
    {
        try {
            $this->transaction->begin();

            $this->listingItemRepository->persist($listingItem);

            $this->localityRepository
                 ->saveLocalityToUserList(
                     $listingItem->locality,
                     $listingItem->listing->getRowData()['userID']
                 );

            $this->transaction->commit();

            return $listingItem;

        } catch (\DibiException $e) {

            $this->transaction->rollback();
            if ($e->getCode() == 1062) {
                throw new ListingItemDayAlreadyExistsException;
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     * @param int $day
     * @param Listing $listing
     */
    public function removeListingItem($day, Listing $listing)
    {
        $this->listingItemRepository
            ->removeListingItem(
                $day,
                $listing->listingID
            );
    }

    /**
     * @param $listingItemID
     * @param Listing $listing
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    public function getListingItemByID(
        $listingItemID,
        Listing $listing
    ) {
        return $this->listingItemRepository->getListingItem(
            $listingItemID,
            $listing->listingID
        );
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return ListingItem
     * @throws ListingItemNotFoundException
     */
    public function getListingItemByDay(
        $day,
        Listing $listing
    ) {
        return $this->listingItemRepository->getByDayInListing(
            $day,
            $listing->listingID
        );
    }

    /**
     * @param int $day
     * @param Listing $listing
     * @return mixed
     * @throws \DibiException
     * @throws DayExceedCurrentMonthException
     * @throws ListingItemNotFoundException
     */
    public function shiftCopyOfListingItemDown(
        $day,
        Listing $listing
    ) {
        // we do NOT want to shift the last item
        if ($day >= $listing->getNumberOfDaysInMonth()) {
            throw new DayExceedCurrentMonthException;
        }

        return $this->listingItemRepository
                    ->shiftCopyOfListingItemDown(
                        $day,
                        $listing->listingID
                    );
    }

    /**
     * @param array $listingItems
     * @return array Array of ListingItemDecorators
     */
    public function createListingItemDecoratorsCollection(array $listingItems)
    {
        return $this->itemService->createDecoratorsCollection($listingItems);
    }

    /**
     * @param Listing $listing
     * @return ListingItemDecorator[]
     */
    public function generateEntireTable(
        Listing $listing
    ) {
        $listing->checkEntityState();

        $collectionOfDecorators = $this->createListingItemDecoratorsCollection(
            $listing->listingItems
        );

        return $this->itemService->generateListingItemDecoratorsForEntireTable(
            $collectionOfDecorators,
            $listing->getPeriod()
        );
    }

    /**
     * @param Locality $locality
     * @return Locality
     * @throws \DibiException
     */
    public function setupLocalityEntity(Locality $locality)
    {
        return $this->localityRepository->setupLocality($locality);
    }

    /**
     * @param WorkedHours $workedHours
     * @return WorkedHours|mixed
     * @throws \DibiException
     */
    public function setupWorkedHoursEntity(WorkedHours $workedHours)
    {
        return $this->workedHoursRepository->setupWorkedHours($workedHours);
    }

}