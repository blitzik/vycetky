<?php

use Tester\Assert;

require '../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$locality = new \App\Model\Entities\Locality();
$_er->makeAlive($locality, 1);

$workedHours = new \App\Model\Entities\WorkedHours();
$_er->makeAlive($workedHours, 1);

$baseItems = [];
$items = [];
for ($i = 2; $i <= 4; $i += 2) { // Items days 1 and 3

    $item = new \App\Model\Entities\ListingItem();
    $item->setDay($i - 1);
    $item->locality = $locality;
    $item->workedHours = $workedHours;

    $_er->makeAlive($item, $i - 1);

    $baseItems[] = $item;

    $item2 = new \App\Model\Entities\ListingItem();
    $item2->setDay($i - 1);
    $item2->locality = $locality;
    $item2->workedHours = $workedHours;

    $_er->makeAlive($item2, $i);

    $items[] = $item2;
}

// Items for 1st day and 3rd day are identical


// For 6th day, there is Listing Item with different worked hours
// but same Locality.
$loc2 = new \App\Model\Entities\Locality();
$_er->makeAlive($loc2, 2);

// This entity is just for base items, it does not have equal day entity in $items
$li = new \App\Model\Entities\ListingItem();
$li->setDay(6);
$li->locality = $loc2;
$li->workedHours = $workedHours;
$_er->makeAlive($li, 20);

$baseItems[] = $li;


// and last 2 entities for same day but with different localities and worked hours
$li3 = new \App\Model\Entities\ListingItem();
$li3->setDay(10);
$li3->locality = $locality;
$li3->workedHours = $workedHours;
$_er->makeAlive($li3, 30);

$baseItems[] = $li3;

$wh = new \App\Model\Entities\WorkedHours();
$_er->makeAlive($wh, 5);

$li4 = new \App\Model\Entities\ListingItem();
$li4->setDay(10);
$li4->locality = $loc2;
$li4->workedHours = $wh;
$_er->makeAlive($li4, 40);

$items[] = $li4;

$mergedItems = $service->mergeListingItems($baseItems, $items);

$resultArray = [
    1 => [$baseItems[0]], // entities were same, take only first one
    3 => [$baseItems[1]], // same here
    6 => [$li], // entity did not have equivalent for 6th day in the other list
    10 => [$li3, $li4] // if there is more entities for one day and these entities are not same, we need both of them
];

Assert::same($resultArray, $mergedItems);