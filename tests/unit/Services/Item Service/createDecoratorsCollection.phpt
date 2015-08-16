<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$listing = new \App\Model\Entities\Listing(2015, 5, 1);
$_er->makeAlive($listing, 1);

// ------

$items = [];

$item = new \App\Model\Domain\FillingItem(new DateTime('2015-05-01'));

$items[] = $item;

// ------

$wh = new \App\Model\Entities\WorkedHours(
    '06:00', '16:00', '01:00'
);

$_er->makeAlive($wh, 1);

$loc = new \App\Model\Entities\Locality('Praha');

$_er->makeAlive($loc, 1);

$item2 = new \App\Model\Entities\ListingItem(
    2, $listing, $wh, $loc
);

$items[] = $item2;

// ------

Assert::exception(function () use ($service, $items) { // try to pass detached isntance
    $service->createDecoratorsCollection($items);
}, 'Exceptions\Logic\InvalidArgumentException',
    'Only instances of Entities\ListingItem or Domain\FillingItem can be processed');

$_er->makeAlive($items[1], 2);

$decorators = $service->createDecoratorsCollection($items);

Assert::exception(function () use ($service, $items) {
    array_unshift($items, new \App\Model\Entities\Locality('Brno'));
    $service->createDecoratorsCollection($items);
}, 'Exceptions\Logic\InvalidArgumentException',
   'Only instances of Entities\ListingItem or Domain\FillingItem can be processed');