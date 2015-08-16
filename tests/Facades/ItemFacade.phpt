<?php

use Tester\Assert;

$container = require './BaseFacadeTest.php';

class ItemFacadeTest extends BaseFacadeTest
{
    /**
     * @var \App\Model\Facades\ItemFacade
     */
    private $itemFacade;

    public function __construct(
        \Nette\DI\Container $container
    )
    {
        parent::__construct($container);

        $this->itemFacade = $container->getService('itemFacade');
    }

    public function testSaveListingItem()
    {
        $listing = $this->generateListing(2015, 5);
        $locality = $this->setupLocality('Praha');
        $workedHours = $this->setupWorkedHours(
            new InvoiceTime('06:00'),
            new InvoiceTime('16:00'),
            new InvoiceTime('01:00')
        );

        $listingItem = new \App\Model\Entities\ListingItem(
            1, $listing, $workedHours, $locality
        );

        $item = $this->itemFacade->saveListingItem($listingItem);

        Assert::same($listingItem, $item);

        Assert::count(1, $listing->listingItems);

        $res = $this->connection
                    ->query(
                        'SELECT userID, localityID,
                                COUNT(localityUserID) as numberOfUserLocalities
                         FROM locality_user
                         WHERE userID = ?', self::TEST_USER_ID)->fetch();

        Assert::same(1, $res['userID']);
        Assert::same(1, $res['localityID']);
        Assert::same(1, $res['numberOfUserLocalities']);

        Assert::exception(
            function () use ($listing, $workedHours, $locality) {
                $item = new \App\Model\Entities\ListingItem(
                    1, $listing, $workedHours, $locality
                );

                $this->itemFacade->saveListingItem($item);
                
            },
            '\Exceptions\Runtime\ListingItemDayAlreadyExistsException'
        );

    }

    public function testShiftCopyOfListingItemDown()
    {
        $listing = $this->generateListing(2015, 8);
        $locality = $this->setupLocality('Praha');
        $workedHours = $this->setupWorkedHours(
            new InvoiceTime('06:00'),
            new InvoiceTime('16:00'),
            new InvoiceTime('01:00')
        );

        $insertItem = $this->connection->insert('listing_item', [
            'day' => 30,
            'listingID' => $listing->listingID,
            'workedHoursID' => $workedHours->workedHoursID,
            'localityID' => $locality->localityID,
            'description' => 'test item',
            'descOtherHours' => 'other hours description'
        ]); // not inserted item yet

        Assert::exception( // try to copy item, that does not exist
            function () use ($listing) {
                $this->itemFacade->shiftCopyOfListingItemDown(30, $listing);
            },
            // because copy is not created, function won't find it and therefore
            // it cannot return the entity that represents the new item
            '\Exceptions\Runtime\ListingItemNotFoundException'
        );

        $insertItem->execute();

        $this->itemFacade->shiftCopyOfListingItemDown(30, $listing);

        Assert::count(2, $listing->listingItems);

        Assert::same($listing->listingItems[0]->day + 1, $listing->listingItems[1]->day);

        Assert::same(
            $listing->listingItems[0]->getRowData()['localityID'],
            $listing->listingItems[1]->getRowData()['localityID']
        );

        Assert::same(
            $listing->listingItems[0]->getRowData()['workedHoursID'],
            $listing->listingItems[1]->getRowData()['workedHoursID']
        );

        Assert::exception(
            function () use ($listing) {
                $this->itemFacade->shiftCopyOfListingItemDown(31, $listing);
            },
            '\Exceptions\Runtime\DayExceedCurrentMonthException'
        );

    }


}

$test = new ItemFacadeTest($container);
$test->run();