<?php

$container = require '../bootstrap.php';

abstract class BaseFacadeTest extends \Tester\TestCase
{
    const TEST_USER_ID = 1;

    /**
     * @var array
     */
    protected $defaultWorkedHours = [
        '06:00',
        '16:00',
        '01:00',
        '00:00'
    ];

    /**
     * @var \LeanMapper\Connection
     */
    protected $connection;

    /**
     * @var \LeanMapper\IMapper
     */
    protected $mapper;

    /**
     * @var \LeanMapper\IEntityFactory
     */
    protected $entityFactory;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var \Nette\DI\Container
     */
    protected $container;

    public function __construct(
        \Nette\DI\Container $container
    ) {
        $this->container = $container;

        $this->connection = $container->getService('leanMapper.connection');
        $this->entityFactory = $container->getService('entityFactory');
        $this->mapper = $container->getService('standardMapper');

        $this->transaction = $container->getService('transaction');
    }

    /**
     * This method is called before a test is executed.
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Tester\Environment::lock('database', __DIR__ . '/../temp');

        $this->connection->loadFile(__DIR__ . '/../DBstructure.sql');
    }

    /**
     * @param \LeanMapper\Entity $entity
     * @param $id
     * @throws \LeanMapper\Exception\InvalidStateException
     */
    protected function makeEntityAlive(\LeanMapper\Entity $entity, $id)
    {
        $entity->makeAlive($this->entityFactory, $this->connection, $this->mapper);
        $entity->attach($id);
    }

    /**
     * @param InvoiceTime $workStart
     * @param InvoiceTime $workEnd
     * @param InvoiceTime $lunch
     * @param InvoiceTime|null $otherHours
     * @return \App\Model\Entities\WorkedHours
     */
    protected function setupWorkedHours(
        InvoiceTime $workStart,
        InvoiceTime $workEnd,
        InvoiceTime $lunch,
        InvoiceTime $otherHours = null
    ) {
        $otherHours = $otherHours === null ? new \InvoiceTime(null) : $otherHours;

        $arr = ['workStart' => $workStart->getTime(), 'workEnd' => $workEnd->getTime(),
            'lunch' => $lunch->getTime(), 'otherHours' => $otherHours->getTime()];

        try {
            $this->connection->query(
                'INSERT INTO [worked_hours]', $arr
            );

            $id = $this->connection->getInsertId();

        } catch (\DibiException $e) {
            if ($e->getCode() == 1062) {
                $id = $this->connection->query(
                    'SELECT workedHoursID as id FROM worked_hours
                     WHERE %and', $arr
                )->fetchSingle();
            }
        }

        $workedHours = \App\Model\Entities\WorkedHours::loadState(
            $workStart, $workEnd, $lunch, $otherHours
        );

        $this->makeEntityAlive($workedHours, $id);

        return $workedHours;
    }

    /**
     * @param $localityName
     * @return \App\Model\Entities\Locality
     */
    protected function setupLocality($localityName)
    {
        $arr = ['name' => $localityName];
        try {
            $this->connection->query(
                'INSERT INTO [locality]', $arr
            );

            $id = $this->connection->getInsertId();
        } catch (\DibiException $e) {
            if ($e->getCode() == 1062) {
                $id = $this->connection->query(
                    'SELECT localityID as id FROM locality
                     WHERE %and', $arr
                )->fetchSingle();
            }
        }

        $locality = \App\Model\Entities\Locality::loadState(
            $localityName
        );

        $this->makeEntityAlive($locality, $id);

        return $locality;
    }

    /**
     * @return \App\Model\Entities\Listing
     */
    protected function generateListing(
        $year = 2015,
        $month = 5,
        $listingDescription = 'test listing',
        $user = self::TEST_USER_ID,
        $hourlyWage = 150
    ) {
        $arr = ['year' => $year, 'month' => $month,
                'description' => $listingDescription, 'userID' => $user,
                'hourlyWage' => $hourlyWage];

        $this->connection->query('INSERT INTO [listing]', $arr);
        $id = $this->connection->getInsertId();

        $listing = \App\Model\Entities\Listing::loadState(
            $year, $month, $user, $listingDescription, $hourlyWage
        );

        $listing->loadState($year, $month, $user);

        $this->makeEntityAlive($listing, $id);

        return $listing;
    }

    /**
     * @param \App\Model\Entities\Listing $listing
     * @param array $workedHours
     * @param array $localities
     * @return void
     * @throws Exception
     */
    protected function generateItems(
        \App\Model\Entities\Listing $listing,
        array $workedHours,
        array $localities
    ) {
        dump($workedHours);
        $c = count($workedHours);
        if ($c != count($localities)) {
            throw new Exception('Wrong amount of array items');
        }

        $itemsData = [];
        for ($i = 0; $i < $c; $i++) {
            $itemsData['listingID'][] = $listing->listingID;

            $wh = $this->setupWorkedHours(
                new InvoiceTime($workedHours[$i][0]),
                new InvoiceTime($workedHours[$i][1]),
                new InvoiceTime($workedHours[$i][2]),
                new InvoiceTime($workedHours[$i][3])
            );

            $itemsData['workedHoursID'][] = $wh->workedHoursID;

            $loc = $this->setupLocality($localities[$i]);

            $itemsData['localityID'][] = $loc->localityID;

            $itemsData['day'][] = $i + 1;
            $itemsData['description'][] = null;
            $itemsData['descOtherHours'][] = isset($workedHours[$i]['descOtherHours']) ?
                                             $workedHours[$i]['descOtherHours']:
                                             null;

            /*$item = new \App\Model\Entities\ListingItem();
            $item->assign($arr);

            $items[] = $item;*/

        }

        $this->connection->query('INSERT INTO [listing_item] %m', $itemsData);
        /*$id = $this->connection->getInsertId(); // first inserted ID

        foreach ($items as $item) {
            $this->makeEntityAlive($item, $id);
            $id++;
        }

        return $items;*/
    }

    /**
     * @param $username
     * @param $email
     * @return \App\Model\Entities\User
     * @throws DibiException
     */
    public function generateUser(
        $username,
        $email = null
    ) {
        $values = [
            'username' => $username,
            'password' => \Nette\Security\Passwords::hash('abcd'),
            'ip' => '127.0.0.1',
            'lastLogin' => '2015-05-01 06:00:00',
            'lastIP' => '127.0.0.1'
        ];

        if ($email === null) {
            $email = $username . '@' . $username . '.abc';
        }

        $values['email'] = $email;

        $this->connection->insert('user', $values)->execute();
        $id = $this->connection->getInsertId();

        $user = \App\Model\Entities\User::loadState(
            $values['username'],
            $values['password'],
            $values['email'],
            $values['ip']
        );

        $this->makeEntityAlive($user, $id);

        return $user;
    }
}

return $container;