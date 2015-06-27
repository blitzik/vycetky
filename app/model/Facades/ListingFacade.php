<?php

namespace App\Model\Facades;

use Exceptions\Runtime\CollisionItemsOccurrenceException;
use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\CollisionItemsSelectionException;
use App\Model\Repositories\ListingItemRepository;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Repositories\ListingRepository;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Entities\WorkedHours;
use App\Model\Entities\ListingItem;
use App\Model\Services\ItemService;
use App\Model\Entities\Listing;
use Nette\Security\User;
use Tracy\Debugger;
use Nette\Object;

class ListingFacade extends Object
{
    /**
     * @var ListingItemRepository
     */
    private $listingItemRepository;

    /**
     * @var ListingRepository
     */
    private $listingRepository;

    /**
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var ItemService
     */
    private $itemService;

    /**
     * @var ItemFacade
     */
    private $itemFacade;

    /**
     * @var User
     */
    private $user;


    public function __construct(
        ListingItemRepository $listingItemRepository,
        ListingRepository $listingRepository,
        \Transaction $transaction,
        ItemService $itemService,
        ItemFacade $itemFacade,
        User $user
    ) {
        $this->listingItemRepository = $listingItemRepository;
        $this->listingRepository = $listingRepository;
        $this->transaction = $transaction;
        $this->itemService = $itemService;
        $this->itemFacade = $itemFacade;
        $this->user = $user;
    }

    /**
     * @param Listing|NULL $listing
     * @return int
     */
    public function saveListing(Listing $listing)
    {
        return $this->listingRepository->persist($listing);
    }

    /**
     * @param Listing $listing
     * @return mixed
     */
    public function removeListing(Listing $listing)
    {
        return $this->listingRepository->delete($listing);
    }

    /**
     * @param $id
     * @return Listing
     */
    public function getEntireListingByID($id)
    {
        return $this->listingRepository
                    ->getEntireListingByID($id, $this->user->id);
    }

    /**
     * @param $id
     * @return Listing
     */
    public function getListingByID($id)
    {
        return $this->listingRepository->getListingByID($id);
    }

    /**
     * @param $year
     * @param $month
     * @return Listing[]
     */
    public function findListingsByPeriod($year, $month = null)
    {
        return $this->listingRepository
                    ->findUserListingsByPeriod(
                        $this->user->id,
                        $year,
                        $month
                    );
    }

    /**
     * @param $year
     * @param $month
     * @return array
     */
    public function findPartialListingsDataForSelect($year, $month)
    {
        $listings =  $this->listingRepository
                          ->findPartialListings(
                              $this->user->id,
                              $year,
                              $month
                          );

        $result = array();
        foreach ($listings as $listing) {
            $result[$listing->listingID] = ' [#'.$listing->listingID .'] ' . $listing->entireDescription();
        }

        return $result;
    }

    /**
     * @param Listing $listing
     * @param bool $withItems
     * @param null $userID
     * @return Listing
     * @throws \DibiException
     */
    public function establishListingCopy(
        Listing $listing,
        $withItems = true,
        $userID = null
    ) {
        if ($listing->isDetached()) {
            throw new InvalidArgumentException('Argument $listing must be attached Entity.');
        }

        $newItems = [];
        if ($withItems === true) {
            if (count($listing->listingItems) > 0) {
                $newItems = $this->itemService
                                 ->createItemsCopies($listing->listingItems);
            }
        }

        $newListing = clone $listing;
        if ($userID !== null) {
            $newListing->user = $userID;
        }

        try {
            $this->transaction->begin();

            $this->listingRepository->persist($newListing);

            if (count($newItems) > 0) {
                $this->persistListingItems($newItems, $newListing);
            }

            $this->transaction->commit();

            return $newListing;

        } catch (\DibiException $e) {

            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $listing
     * @param WorkedHours $workedHours
     * @param bool $createNewListing
     * @param array $selectedItemsIDsToChange
     * @return array
     * @throws \DibiException
     * @throws NegativeResultOfTimeCalcException
     */
    public function changeItemsInListing(
        Listing $listing,
        WorkedHours $workedHours,
        $createNewListing = true,
        array $selectedItemsIDsToChange
    ) {
        $listingItems = $listing->listingItems;

        $itemsToChange = [];
        $itemsToCopy = []; // items that won't change

        $selectedItemsToChange = array_flip($selectedItemsIDsToChange);

        foreach ($listingItems as $listingItem) {
            if (array_key_exists($listingItem->listingItemID, $selectedItemsToChange)) {
                $itemsToChange[] = $listingItem;
            } else {
                $itemsToCopy[] = $listingItem;
            }
        }

        try {
            $this->transaction->begin();

            if ($createNewListing === true) {
                $listing = $this->establishListingCopy($listing, false);
            }

            if (count($itemsToCopy) > 0 and $createNewListing === true) {
                    $itemsCopies = $this->itemService
                                        ->createItemsCopies($itemsToCopy);
                    $itemsToCopy = $this->itemService
                                        ->setListingForGivenItems(
                                            $itemsCopies,
                                            $listing
                                        );
            }

            if (count($itemsToChange) > 0) {
                if ($createNewListing === true) {
                    $itemsCopies = $this->itemService
                                        ->createItemsCopies($itemsToChange);
                    $itemsToChange = $this->itemService
                                          ->setListingForGivenItems(
                                              $itemsCopies,
                                              $listing
                                          );
                }

                $workedHours = $this->itemFacade
                                    ->setupWorkedHoursEntity($workedHours);

                foreach ($itemsToChange as $item) {
                    $item->workedHours = $workedHours;

                    if ($workedHours->otherHours->toSeconds() == 0) {
                        $item->descOtherHours = null;
                    }
                }
            }

            if ($createNewListing === true) {
                $allItems = array_merge($itemsToChange, $itemsToCopy);
                $this->listingItemRepository->saveListingItems($allItems);

            } else {

                $this->listingItemRepository
                     ->updateListingItemsWorkedHours(
                         $itemsToChange,
                         $workedHours
                     );
            }

            $this->transaction->commit();

            return ['listing'      => $listing,
                    'changedItems' => $itemsToChange];

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $listing
     * @param $description
     * @param array $recipients
     * @param array $ignoredItemsIDs
     * @return Listing[]
     * @throws \DibiException
     */
    public function shareListing(
        Listing $listing,
        $description,
        array $recipients,
        array $ignoredItemsIDs = null
    ) {
        $listingItems = $listing->listingItems;

        if (isset($ignoredItemsIDs)) {
            $ignoredItemsIDs = array_flip($ignoredItemsIDs);
            foreach ($listingItems as $key => $item) {
                if (array_key_exists($item->listingItemID, $ignoredItemsIDs)) {
                    unset($listingItems[$key]);
                }
            }
        }

        $newListings = [];
        foreach ($recipients as $recipientID) {
            $newListing = clone $listing;
            $newListing->user = $recipientID;
            $newListing->description = $description;
            $newListing->hourlyWage = null;

            $newListings[] = $newListing;
        }

        try {
            $this->transaction->begin();

            $this->listingRepository->saveListings($newListings);

            $items = [];
            foreach ($newListings as $listing) {
                $newItemsForListing = $this->itemService
                                           ->createItemsCopies($listingItems);
                $newItemsForListing = $this->itemService
                                           ->setListingForGivenItems(
                                               $newItemsForListing,
                                               $listing
                                           );

                $items = array_merge($items, $newItemsForListing);
                if (count($items) > 120) {
                    $this->listingItemRepository->saveListingItems($items);
                    unset($items);

                    $items = [];
                }
            }
            // save the rest items
            if (!empty($items)) {
                $this->listingItemRepository->saveListingItems($items);
            }

            $this->transaction->commit();

            return $newListings;

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param Listing $baseListing
     * @param Listing $listing
     * @return array
     */
    public function getMergedListingsItemsForEntireTable(Listing $baseListing, Listing $listing)
    {
        if (!$this->haveListingsSamePeriod($baseListing, $listing)) {
            throw new InvalidArgumentException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        $items = $this->itemService
                      ->mergeListingItems(
                          $baseListing->listingItems,
                          $listing->listingItems
                      );

        $days = $baseListing->getDaysInMonth();

        $result = array();
        for ($day = 1; $day <= $days; $day++) {
            if (!array_key_exists($day, $items)) {
                $listingItem = new ListingItem();
                $listingItem->day = $day;
                $result[$day][] = new ListingItemDecorator(
                    $listingItem,
                    $baseListing->year,
                    $baseListing->month
                );
            } else {
                foreach ($items[$day] as $item) {
                    $result[$day][] = new ListingItemDecorator(
                        $item,
                        $baseListing->year,
                        $baseListing->month
                    );
                }
            }
        }
        
        return $result;
    }

    /**
     * @param Listing $baseListing
     * @param Listing $listingToMerge
     * @param array $selectedCollisionItems
     * @return Listing
     * @throws CollisionItemsOccurrenceException
     * @throws CollisionItemsSelectionException
     * @throws \DibiException
     */
    public function mergeListings(
        Listing $baseListing,
        Listing $listingToMerge,
        array $selectedCollisionItems = []
    ) {
        if (!$this->haveListingsSamePeriod($baseListing, $listingToMerge)) {
            throw new InvalidArgumentException(
                'Given Listings must have same Period(Year and Month).'
            );
        }

        $items = $this->itemService->getMergedListOfItems(
            $baseListing,
            $listingToMerge,
            $selectedCollisionItems
        );

        try {
            $this->transaction->begin();

            $newListing = new Listing();
            $newListing->setPeriod($baseListing->year, $baseListing->month);
            $newListing->user = $this->user->id;

            $this->saveListing($newListing);

            $this->itemService->setListingForGivenItems($items, $newListing);

            $this->listingItemRepository->saveListingItems($items);

            $this->transaction->commit();

            return $newListing;

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param array $listingItems
     * @param Listing $listing
     */
    private function persistListingItems(array $listingItems, Listing $listing)
    {
        $itemsRelatedToNewListing = $this->itemService
                                         ->setListingForGivenItems(
                                             $listingItems,
                                             $listing
                                         );

        $this->listingItemRepository->saveListingItems($itemsRelatedToNewListing);
    }

    /**
     * @param Listing $base
     * @param Listing $second
     * @return bool
     */
    public function haveListingsSamePeriod(Listing $base, Listing $second)
    {
        if ($base->year === $second->year and $base->month === $second->month) {
            return true;
        }

        return false;
    }

}