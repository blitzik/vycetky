<?php

use Tester\Assert;

require '../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$listing = new \App\Model\Entities\Listing();
$listing->setPeriod(2015, 5);

$_er->makeAlive($listing, 1);

$items = [];
for ($i = 1; $i <= 2; $i++) {
    $item = new \App\Model\Entities\ListingItem();
    $item->setDay($i);

    $_er->makeAlive($item, $i);

    $items[$i] = $item;
}

$newItems = $service->setListingForGivenItems($items, $listing);

foreach ($newItems as $item) {
    Assert::same(
        $items[$item->day]->listing->listingID,
        $item->getRowData()['listingID']
    );
}

Assert::exception(function () use ($service, $items) {

    $listing = new \App\Model\Entities\Listing();

    $service->setListingForGivenItems($items, $listing);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Only attached(not detached) Listing entity can pass!');

Assert::exception(function () use ($service, $items, $listing) {

    array_unshift($items, new \App\Model\Entities\Locality());

    $service->setListingForGivenItems($items, $listing);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Invalid set of ListingItems given.');