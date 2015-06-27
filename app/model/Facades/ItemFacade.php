<?php

namespace App\Model\Facades;

use Exceptions\Runtime\ListingItemDayAlreadyExistsException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\DayExceedCurrentMonthException;
use Exceptions\Runtime\ListingItemNotFoundException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use App\Model\Repositories\WorkedHoursRepository;
use App\Model\Repositories\ListingItemRepository;
use App\Model\Repositories\LocalityRepository;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Entities\WorkedHours;
use App\Model\Entities\ListingItem;
use App\Model\Services\ItemService;
use App\Model\Entities\Locality;
use App\Model\Entities\Listing;
use Nette\Security\User;
use Tracy\Debugger;
use Nette\Object;

class ItemFacade extends Object
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

    /**
     * @var User
     */
    private $user;


    public function __construct(
        ListingItemRepository $listingItemRepository,
        WorkedHoursRepository $workedHoursRepository,
        LocalityRepository $localityRepository,
        \Transaction $transaction,
        ItemService $itemService,
        User $user
    ) {
        $this->listingItemRepository = $listingItemRepository;
        $this->workedHoursRepository = $workedHoursRepository;
        $this->localityRepository = $localityRepository;
        $this->transaction = $transaction;
        $this->itemService = $itemService;
        $this->user = $user;
    }

    /**
     * @param ListingItem $listingItem
     * @param array $values
     * @return ListingItem
     * @throws \DibiException
     * @throws ShiftEndBeforeStartException
     * @throws NegativeResultOfTimeCalcException
     * @throws ListingItemDayAlreadyExistsException
     */
    public function saveListingItem(
        ListingItem $listingItem,
        array $values
    ) {
        $wh = new WorkedHours();
        $wh->setHours(
            new \InvoiceTime($values['workStart']),
            new \InvoiceTime($values['workEnd']),
            new \InvoiceTime($values['lunch']),
            new \InvoiceTime($values['otherHours'])
        );

        try {
            $this->transaction->begin();

            if ($listingItem->isDetached()) {
                $listingItem->listing = ($values['listing']);
                $listingItem->day = (new \DateTime($values['day']))->format('j');
                $listingItem->locality = $this->setupLocalityEntity($values['locality']);
                $listingItem->workedHours = $this->setupWorkedHoursEntity($wh);
                $listingItem->description = $values['description'];
                $listingItem->descOtherHours = $values['descOtherHours'];

                $this->localityRepository
                     ->saveLocalityToUserList($listingItem->locality, $values['userID']);

            } else {
                //if ($listingItem->locality->name != $values['locality']) {
                    $listingItem->locality = $this->setupLocalityEntity($values['locality']);

                    $this->localityRepository
                         ->saveLocalityToUserList($listingItem->locality, $values['userID']);
                //}

                if (!$this->compare($listingItem->workedHours, $wh)) {
                    $listingItem->workedHours = $this->setupWorkedHoursEntity($wh);
                }

                if ($listingItem->description != $values['description']) {
                    $listingItem->description = $values['description'];
                }

                if ($listingItem->descOtherHours != $values['descOtherHours']) {
                    $listingItem->descOtherHours = $values['descOtherHours'];
                }
            }

            $this->listingItemRepository->persist($listingItem);

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
        if ($day >= $listing->getDaysInMonth()) {
            throw new DayExceedCurrentMonthException;
        }

        return $this->listingItemRepository
            ->shiftCopyOfListingItemDown(
                $day,
                $listing->listingID
            );
    }

    /**
     * @param ListingItem[] $listingItems Array of ListingItems
     * @return array Array of ListingItemDecorators
     */
    public function createListingItemDecoratorsCollection(
        array $listingItems,
        $year,
        $month
    ) {
        return $this->itemService->createDecoratorsCollection(
            $listingItems,
            $year,
            $month
        );
    }

    /**
     * @param ListingItemDecorator[] $listingItemsDecorators
     * @param \DateTime $period
     * @return ListingItemDecorator[] Returns Array of ListingItemDecorators for every day of given Month
     */
    public function generateListingItemDecoratorsForEntireTable(
        array $listingItemsDecorators,
        \DateTime $period
    ) {
        return $this->itemService
                    ->generateListingItemDecoratorsForEntireTable(
                        $listingItemsDecorators,
                        $period->format('Y'),
                        $period->format('n')
                    );
    }

    /**
     * @param ListingItem[] $listingItems
     * @param \DateTime $period
     * @return ListingItemDecorator[]
     */
    public function generateEntireTable(
        array $listingItems,
        \DateTime $period
    ) {
        $collectionOfDecorators = $this->createListingItemDecoratorsCollection(
            $listingItems,
            $period->format('Y'),
            $period->format('n')
        );

        return $this->generateListingItemDecoratorsForEntireTable(
            $collectionOfDecorators,
            $period
        );
    }

    /**
     * @param $localityName
     * @return mixed
     * @throws \DibiException
     */
    public function setupLocalityEntity($localityName)
    {
        try {
            $locality = new Locality();
            $locality->name = $localityName;
            $this->localityRepository->persist($locality);

            return $locality;

        } catch (\DibiException $e) {

            if ($e->getCode() === 1062) {
                return $this->localityRepository->findByName($localityName);
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     * @param WorkedHours $workedHours
     * @return WorkedHours|mixed
     * @throws \DibiException
     * @throws ShiftEndBeforeStartException
     * @throws NegativeResultOfTimeCalcException
     */
    public function setupWorkedHoursEntity(WorkedHours $workedHours)
    {
        try {
            $this->workedHoursRepository->persist($workedHours);

            return $workedHours;

        } catch (\DibiException $e) {

            if ($e->getCode() === 1062) {
                return $this->workedHoursRepository
                            ->getByValues($workedHours->getRowData());
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     * @param WorkedHours $wh
     * @param WorkedHours $wh2
     * @return bool
     */
    private function compare(WorkedHours $wh, WorkedHours $wh2)
    {
        $compare = ['workStart', 'workEnd', 'lunch', 'otherHours'];

        foreach ($compare as $prop) {
            if ($wh->{$prop}->compare($wh2->{$prop}) !== 0) {
                return false;
            }
        }

        return true;
    }

}