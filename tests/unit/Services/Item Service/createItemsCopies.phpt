<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$listing = \App\Model\Entities\Listing::loadState(2015, 1, 1);

$_er->makeAlive($listing, 1);

$locality = new \App\Model\Entities\Locality();

$_er->makeAlive($locality, 1);

$workedHours = new \App\Model\Entities\WorkedHours();

$_er->makeAlive($workedHours, 1);

$listingItems = [];
for ($i = 1; $i <= 2; $i++) {
    $item = \App\Model\Entities\ListingItem::loadState(
        $i, $listing, $workedHours, $locality
    );

    $_er->makeAlive($item, $i);

    $listingItems[$i] = $item;
}

$newItems = $service->createItemsCopies($listingItems);

foreach ($newItems as $item) {
    $day = $item->day;
    Assert::true($item->isDetached());

    Assert::same(
        $listingItems[$day]->listing->listingID,
        $item->getRowData()['listingID']
    );

    Assert::same(
        $listingItems[$day]->locality->localityID,
        $item->getRowData()['localityID']
    );

    Assert::same(
        $listingItems[$day]->workedHours->workedHoursID,
        $item->getRowData()['workedHoursID']
    );
}

Assert::exception(function () use ($service, $listingItems) {

    array_unshift($listingItems, new \App\Model\Entities\Locality());

    $service->createItemsCopies($listingItems);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Invalid set of ListingItems given.');

Assert::exception(function () use ($service, $listingItems) {

    // detached entity
    array_unshift($listingItems, new \App\Model\Entities\ListingItem());

    $service->createItemsCopies($listingItems);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Invalid set of ListingItems given.');