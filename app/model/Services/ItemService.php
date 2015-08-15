<?php

namespace App\Model\Services;

use Exceptions\Runtime\NoCollisionListingItemSelectedException;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Domain\ListingItemDecorator;
use App\Model\Time\TimeUtils;
use App\Model\Entities;
use Nette\Object;

class ItemService extends Object
{

    /**
     * @param Entities\ListingItem[] $listingItems
     * @return Entities\ListingItem[] Array of detached entities
     */
    public function createItemsCopies(array $listingItems)
    {
        $collection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem or
                $listingItem->isDetached()) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }
            $collection[] = clone $listingItem;
        }

        return $collection;
    }

    /**
     * @param Entities\ListingItem[] $listingItems
     * @param Entities\Listing $listing
     * @return array
     */
    public function setListingForGivenItems(
        array $listingItems,
        Entities\Listing $listing
    ) {
        if ($listing->isDetached())
            throw new InvalidArgumentException(
                'Only attached(not detached) Listing entity can pass!'
            );

        $newItemsCollection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }
            $listingItem->setListing($listing);

            $newItemsCollection[] = $listingItem;
        }

        return $newItemsCollection;
    }

    /**
     *
     * @param Entities\ListingItem[] $listingItems Array of ListingItems
     * @param int $year
     * @param int $month
     * @return array Array of ListingItemDecorators
     */
    public function createDecoratorsCollection(
        array $listingItems,
        $year,
        $month
    ) {
        $collection = [];
        foreach ($listingItems as $listingItem) {
            if (!$listingItem instanceof Entities\ListingItem) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }

            $collection[$listingItem->day] = new ListingItemDecorator(
                $listingItem,
                $year,
                $month
            );
        }

        return $collection;
    }

    /**
     * @param array $baseItems
     * @param array $items
     * @return array
     */
    public function mergeListingItems(
        array $baseItems,
        array $items
    ) {
        $baseListingItems = array();
        foreach ($baseItems as $listingItem) {
            $this->checkListingItemValidity($listingItem);
            $baseListingItems[$listingItem->day] = $listingItem;
        }

        $listingItems = array();
        foreach ($items as $listingItem) {
            $this->checkListingItemValidity($listingItem);
            $listingItems[$listingItem->day] = $listingItem;
        }

        $resultCollection = array();
        for ($day = 1; $day <= 31; $day++) {
            if (isset($baseListingItems[$day]) and isset($listingItems[$day])) {
                if ($baseListingItems[$day]->compare($listingItems[$day], ['listingItemID', 'listingID'])) {
                    $resultCollection[$day][] = $baseListingItems[$day];
                } else {

                    $resultCollection[$day][] = $baseListingItems[$day];
                    $resultCollection[$day][] = $listingItems[$day];
                }
            } else {

                if (isset($baseListingItems[$day])) {
                    $resultCollection[$day][] = $baseListingItems[$day];
                    continue;
                }

                if (isset($listingItems[$day])) {
                    $resultCollection[$day][] = $listingItems[$day];
                }
            }
        }

        return $resultCollection;
    }

    /**
     * @param ListingItemDecorator[] $listingItemsDecorators
     * @param int $year
     * @param int $month
     * @return ListingItemDecorator[] Returns Array of ListingItemDecorators for every day of given Month
     */
    public function generateListingItemDecoratorsForEntireTable(
        array $listingItemsDecorators,
        $year,
        $month
    ) {
        $daysInMonth = TimeUtils::getNumberOfDaysInMonth($year, $month);

        $list = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            if (array_key_exists($day, $listingItemsDecorators)) {
                if (!$listingItemsDecorators[$day] instanceof ListingItemDecorator) {
                    throw new InvalidArgumentException(
                        'Only instances of ListingItemDecorator with
                         compatible Month and Year can pass.'
                    );
                }
                $list[$day] = $listingItemsDecorators[$day];
            } else {

                $li = new Entities\ListingItem();
                $li->setDay($day);
                $list[$day] = new ListingItemDecorator($li, $year, $month);
            }
        }

        return $list;
    }

    /**
     * @param Entities\Listing $baseListing
     * @param Entities\Listing $listingToMerge
     * @param array $selectedCollisionItems
     * @return array
     * @throws NoCollisionListingItemSelectedException
     */
    public function getMergedListOfItems(
        Entities\Listing $baseListing,
        Entities\Listing $listingToMerge,
        array $selectedCollisionItems = []
    ) {
        $selectedCollisionItems = array_flip($selectedCollisionItems);

        $mergedItems = $this->mergeListingItems(
                                $baseListing->listingItems,
                                $listingToMerge->listingItems
                            );

        $numberOfCheckedCollisionItems = null;
        $items = array();
        foreach ($mergedItems as $day => $listingItems) {
            $numberOfCheckedCollisionItems = 0;

            foreach ($listingItems as $item) {
                if (count($listingItems) > 1) {
                    if (array_key_exists($item->listingItemID, $selectedCollisionItems)) {
                        // it will always make clone of the first found item (from base listing)
                        $items[] = clone $item;
                        break;
                    }
                    $numberOfCheckedCollisionItems++;
                    if ($numberOfCheckedCollisionItems >= 2) {
                        // One day can have max. 2 colliding items
                        // and if none of them is selected, throw exception
                        throw new NoCollisionListingItemSelectedException;
                    }

                } else {

                    $items[] = clone $item;
                }
            }
        }

        return $items;
    }

    /**
     * Checks whether given $listingItem is Instance of ListingItem and is attached
     * (not Detached)
     * @param $listingItem
     */
    private function checkListingItemValidity($listingItem)
    {
        if (!$listingItem instanceof Entities\ListingItem or
             $listingItem->isDetached()) {
            throw new InvalidArgumentException(
                'Only Attached instances of ListingItem can pass.'
            );
        }
    }
}