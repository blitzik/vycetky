<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\ItemService();

$listing = new \App\Model\Entities\Listing(2015, 5, 1);
$_er->makeAlive($listing, 1);

$listing2 = new \App\Model\Entities\Listing(2015, 5, 1);
$_er->makeAlive($listing2, 2);


$locality = new \App\Model\Entities\Locality('Praha');
$_er->makeAlive($locality, 1);

$workedHours = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);
$_er->makeAlive($workedHours, 1);

$baseItems = [];
$items = [];
for ($i = 2; $i <= 4; $i += 2) { // Items days 1 and 3

    $item = new \App\Model\Entities\ListingItem(
        ($i - 1), $listing, $workedHours, $locality
    );
    $_er->makeAlive($item, $i - 1);

    $baseItems[] = $item;

    $item2 = new \App\Model\Entities\ListingItem(
        ($i - 1), $listing, $workedHours, $locality
    );
    $_er->makeAlive($item2, $i);

    $items[] = $item2;
}

// Items for 1st day and 3rd day are identical


// For 6th day, there is Listing Item with different worked hours
// but same Locality.
$loc2 = new \App\Model\Entities\Locality('Brno');
$_er->makeAlive($loc2, 2);

// This entity is just for base items, it does not have equal day entity in $items
$li = new \App\Model\Entities\ListingItem(
    6, $listing2, $workedHours, $loc2
);
$_er->makeAlive($li, 20);

$baseItems[] = $li;


// and last 2 entities for same day but with different localities and worked hours
$li3 = new \App\Model\Entities\ListingItem(
    10, $listing2, $workedHours, $locality
);
$_er->makeAlive($li3, 30);

$baseItems[] = $li3;

$wh = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('15:00'),
    new InvoiceTime('01:00')
);
$_er->makeAlive($wh, 5);

$li4 = new \App\Model\Entities\ListingItem(
    10, $listing2, $wh, $loc2
);
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