<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

// ------

$listing = new \App\Model\Entities\Listing(2015, 1, 1);

$_er->makeAlive($listing, 1);

// ------

$locality = new \App\Model\Entities\Locality('Praha');

$_er->makeAlive($locality, 1);

// ------

$workedHours = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);

$_er->makeAlive($workedHours, 1);

$listingItems = [];
for ($i = 1; $i <= 2; $i++) {
    $item = new \App\Model\Entities\ListingItem(
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

    array_unshift($listingItems, new \App\Model\Entities\Locality('Brno'));

    $service->createItemsCopies($listingItems);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Only attached instances of ' .\App\Model\Entities\ListingItem::class. ' can pass.');

Assert::exception(function () use (
    $service,
    $listingItems,
    $listing,
    $locality,
    $workedHours
) {
    // detached entity
    array_unshift(
        $listingItems,
        new \App\Model\Entities\ListingItem(
            10, $listing, $workedHours, $locality
        )
    );

    $service->createItemsCopies($listingItems);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Only attached instances of ' .\App\Model\Entities\ListingItem::class. ' can pass.');