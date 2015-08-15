<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$listing = \App\Model\Entities\Listing::loadState(2015, 5, 1);
$_er->makeAlive($listing, 1);

$items = [];

$item = new \App\Model\Entities\ListingItem();
$item->setDay(1);
$item->listing = $listing;

$_er->makeAlive($item, 1);

$items[] = $item;

$detachedItem = new \App\Model\Entities\ListingItem();
$detachedItem->setDay(2);

$items[] = $detachedItem;

$decorators = $service->createDecoratorsCollection($items, 2015, 5);

foreach ($decorators as $decorator) {
    Assert::type('App\Model\Domain\ListingItemDecorator', $decorator);
}

Assert::exception(function () use ($service, $items) {
    array_unshift($items, new \App\Model\Entities\Locality());
    $service->createDecoratorsCollection($items, 2015, 5);
}, 'Exceptions\Logic\InvalidArgumentException',
   'Invalid set of ListingItems given.');