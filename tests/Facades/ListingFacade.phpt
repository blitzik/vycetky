<?php

use Tester\Assert;

$container = require './BaseFacadeTest.php';

class ListingFacadeTest extends BaseFacadeTest
{
    /**
     * @var \App\Model\Facades\ListingFacade
     */
    private $listingFacade;

    public function __construct(
        \Nette\DI\Container $container
    )
    {
        parent::__construct($container);

        $this->listingFacade = $container->getService('listingFacade');
    }

    public function testEstablishListingCopy()
    {
        $listing = $this->generateListing();

        $this->generateItems(
            $listing,
            [$this->defaultWorkedHours],
            ['Praha']
        );
        $listingItemsFromOriginal = $listing->listingItems;

        Assert::same(1, count($listingItemsFromOriginal));

        $l = $this->listingFacade->establishListingCopy($listing);

        $listingItemsFromNew = $l->listingItems;
        Assert::same(1, count($listingItemsFromNew));

        $originalData = $listingItemsFromOriginal[0]->getRowData();
        unset($originalData['listingItemID'], $originalData['listingID']);

        $newData = $listingItemsFromNew[0]->getRowData();
        unset($newData['listingItemID'], $newData['listingID']);

        Assert::equal($originalData, $newData);

        Assert::same('test listing', $l->description);
        Assert::same(2015, $l->year);
        Assert::same(5, $l->month);
        Assert::same(150, $l->hourlyWage);
        Assert::same(1, $l->user->userID);

        //////////

        $listing = new \App\Model\Entities\Listing(
            2015, 5, self::TEST_USER_ID
        ); // detached instance

        Assert::exception(function () use ($listing) {

            $this->listingFacade->establishListingCopy($listing);

        }, 'Exceptions\Logic\InvalidArgumentException',
           'Argument $listing must be attached Entity.');
    }

    public function testChangeItemsInListing()
    {
        $listing = $this->generateListing();

        $this->generateItems(
            $listing,
            [
                $this->defaultWorkedHours + ['descOtherHours' => 'test'],
                $this->defaultWorkedHours
            ],
            ['Praha', 'Praha']
        );

        $workedHours = $this->setupWorkedHours(
            new InvoiceTime('05:00'),
            new InvoiceTime('10:00'),
            new InvoiceTime('01:00')
        );

        $new = $this->listingFacade->changeItemsInListing(
            $listing,
            $workedHours,
            true, // new listing
            [1] // we wanna change listing items with ID 1 but not 2
        );

        /** @var \App\Model\Entities\Listing $newListing */
        $newListing = $new['listing'];
        $newItems = $newListing->listingItems;


        $originalData = $listing->listingItems[0]->workedHours->getRowData();
        unset($originalData['workedHoursID']);

        $newData = $newItems[0]->workedHours->getRowData();
        unset($newData['workedHoursID']);

        Assert::notEqual($originalData, $newData); // check first item

        $whOrig = $listing->listingItems[1]->workedHours->getRowData();
        unset($whOrig['workedHoursID']);

        $whNew = $newItems[1]->workedHours->getRowData();
        unset($whNew['workedHoursID']);

        Assert::equal($whOrig, $whNew); // check second item

        // because other hours are set to 00:00 we removed the descriptions
        Assert::null($newItems[0]->descOtherHours);

        Assert::exception(
            function () use ($workedHours) {
                $l = new \App\Model\Entities\Listing(2015, 5, self::TEST_USER_ID);

                $this->listingFacade->changeItemsInListing(
                    $l,
                    $workedHours,
                    true,
                    [1]
                );
            },
            'Exceptions\Logic\InvalidArgumentException',
            'Argument $listing must be attached Entity.'
        );

    }

    public function testShareListing()
    {
        $listing = $this->generateListing();
        $this->generateItems(
            $listing,
            [$this->defaultWorkedHours, $this->defaultWorkedHours],
            ['Praha', 'Praha']
        );

        /** @var \App\Model\Facades\UserManager $userManager */
        $userManager = $this->container->getService('userManager');

        $newUser = new \App\Model\Entities\User(
            'user', 'password', 'user@user.lc', '127.0.0.1'
        );

        $userManager->saveUser($newUser);

        $newListings = $this->listingFacade->shareListing(
            $listing,
            'shared listing',
            [$newUser->userID],
            [2] // ignored item with ID 2
        );

        $newListing = $newListings[0];

        Assert::same(1, count($newListings));

        Assert::notSame($listing->description, $newListing->description);
        Assert::same('shared listing', $newListing->description);
        Assert::null($newListing->hourlyWage);

        $origListingItems = $listing->listingItems;
        $newListingItems = $newListing->listingItems;

        Assert::notSame(count($origListingItems), count($newListingItems));

        Assert::same(1, $newListingItems[0]->day);

        Assert::exception(
            function () {
                $l = new \App\Model\Entities\Listing(2015, 5, self::TEST_USER_ID);

                $this->listingFacade->shareListing(
                    $l,
                    'description',
                    []
                );
            },
            'Exceptions\Logic\InvalidArgumentException',
            'Argument $listing must be attached Entity.'
        );
    }

    public function testGetMergedListingsItemsForEntireTable()
    {
        /*
            3 Situations can occur in one particular day.
            1. same items
            2. different items (different worked hours or location or both of them)
            3. One Listing has Item while the other does not have
        */

        $baseListing = $this->generateListing();
        $this->generateItems(
            $baseListing,
            [
                $this->defaultWorkedHours, // 1st day
                $this->defaultWorkedHours, // 2nd day
                ['05:00', '14:00', '01:00', '01:00'] // 3rd day
            ],
            ['Praha', 'Ostrava', 'Liberec']
        );

        $listing = $this->generateListing(
            2015,
            5,
            'new test description'
        );
        $this->generateItems(
            $listing,
            [
                $this->defaultWorkedHours, // 1st day
                $this->defaultWorkedHours  // 2nd day
            ],
            ['Praha', 'Brno']
        );

        $result = $this->listingFacade->getMergedListingsItemsForEntireTable(
            $baseListing,
            $listing
        );

        Assert::same(31, $listing->getNumberOfDaysInMonth());
        Assert::count(31, $result);

        Assert::count(1, $result[1]); // 1st day
        Assert::count(2, $result[2]); // 2nd day
        Assert::count(1, $result[3]); // 3rd day

        // existing Items become Decorators
        // and the days that did not fit to any Item become FillingItem
        foreach ($result as $day => $items) {
            foreach ($items as $item) {
                if ($day >= 1 and $day <= 3 ) {
                    Assert::type('\App\Model\Domain\ListingItemDecorator', $item);
                } else {
                    Assert::type('\App\Model\Domain\FillingItem', $item);
                }
            }
        }

        Assert::exception(
            function () {
                $l  = new \App\Model\Entities\Listing(2015, 5, self::TEST_USER_ID);
                $l2 = new \App\Model\Entities\Listing(2015, 5, self::TEST_USER_ID);

                $this->listingFacade->getMergedListingsItemsForEntireTable(
                    $l,
                    $l2
                );
            },
            'Exceptions\Logic\InvalidArgumentException',
            'Argument $listing must be attached Entity.'
        );

    }

    public function testMergeListings()
    {
        $baseListing = $this->generateListing();
        $this->generateItems(
            $baseListing,
            [
                $this->defaultWorkedHours,            // 1st day ID 1 same as 6
                $this->defaultWorkedHours,            // 2nd day ID 2 diff than 7
                ['05:00', '14:00', '01:00', '01:00'], // 3rd day ID 3 same as 8
                $this->defaultWorkedHours,            // 4th day ID 4 diff than 9
                $this->defaultWorkedHours             // 5th day ID 5 unique
            ],
            ['Praha', 'Ostrava', 'Liberec', 'Trutnov', 'Jilemnice']
        );

        $listing = $this->generateListing(
            2015,
            5,
            'new test description'
        );
        $this->generateItems(
            $listing,
            [
                $this->defaultWorkedHours,            // 1st day ID 6 same as 1
                $this->defaultWorkedHours,            // 2nd day ID 7 diff than 2
                ['05:00', '14:00', '01:00', '01:00'], // 1st day ID 8 same as 3
                $this->defaultWorkedHours             // 2nd day ID 9 diff than 4
            ],
            ['Praha', 'Brno', 'Liberec', 'Borovnice']
        );

        Assert::exception(
            function () use ($baseListing, $listing) {
                $this->listingFacade->mergeListings(
                    $baseListing,
                    $listing,
                    [] // failed in first day where are collision items
                );
            },
            'Exceptions\Runtime\NoCollisionListingItemSelectedException'
        );

        Assert::exception(
            function () use ($baseListing, $listing) {
                $this->listingFacade->mergeListings(
                    $baseListing,
                    $listing,
                    [1] // Item that does not have equivalent Item in the other Listing
                        // It means that there are still 2 or more colliding items in one or more particular days
                );
            },
            'Exceptions\Runtime\NoCollisionListingItemSelectedException'
        );

        $resultListing = $this->listingFacade->mergeListings(
            $baseListing,
            $listing,
            [2, 9, 7, 4], // We can choose both Colliding Items in one day, because
                          // it takes the first item and then continues to another day
            $baseListing->getRowData()['userID']
        );

        $resultListingItems = $resultListing->listingItems;

        Assert::count(5, $resultListingItems);

        // Check if localities and worked hours fits to the new Listing by
        // merging algorithm
        $asserts = [[1, 1], [2, 1], [3, 3], [4, 1], [5, 1]];
        for ($i = 0; $i < count($resultListingItems); $i++) {
            $j = 0;
            $data = $resultListingItems[$i]->getRowData();

            Assert::same($asserts[$i][$j], $data['localityID']);
            Assert::same($asserts[$i][$j + 1], $data['workedHoursID']);
        }

    }

    public function testHaveListingsSamePeriod()
    {
        $listing = new \App\Model\Entities\Listing(2015, 5, self::TEST_USER_ID);

        $listing2 = new \App\Model\Entities\Listing(2015, 5, 1);

        $result = $this->listingFacade->haveListingsSamePeriod($listing, $listing2);

        Assert::true($result);

        $listing3 = new \App\Model\Entities\Listing(2015, 3, 1);

        $result = $this->listingFacade->haveListingsSamePeriod($listing, $listing3);

        Assert::false($result);
    }

}

$t = new ListingFacadeTest($container);
$t->run();