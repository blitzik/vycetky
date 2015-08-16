<?php

require __DIR__ . '/../../../vendor/autoload.php';

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

$configurator = new Nette\Configurator;

//$configurator->enableDebugger(__DIR__ . '/log');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->addDirectory(__DIR__ . '/../libs')
    ->register();

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/config.test.local.neon');

$container = $configurator->createContainer();

\Tester\Helpers::purge(__DIR__ . '/log');

class EntityResuscitator extends \Nette\Object
{
    /**
     * @var \LeanMapper\IMapper
     */
    private $mapper;

    /**
     * @var \LeanMapper\Connection
     */
    private $connection;

    /**
     * @var \LeanMapper\IEntityFactory
     */
    private $entityFactory;

    public function __construct(
        \LeanMapper\IMapper $mapper,
        \LeanMapper\Connection $connection,
        \LeanMapper\IEntityFactory $entityFactory
    ) {
        $this->mapper = $mapper;
        $this->connection = $connection;
        $this->entityFactory = $entityFactory;
    }

    /**
     * @param \LeanMapper\Entity $entity
     * @param $id
     * @return void
     */
    public function makeAlive(\LeanMapper\Entity $entity, $id)
    {
        \Nette\Utils\Validators::assert($id, 'numericint:1..');

        $entity->makeAlive(
            $this->entityFactory,
            $this->connection,
            $this->mapper
        );

        $entity->attach($id);
    }
}

$connection = new \LeanMapper\Connection(
    ['driver' => 'mysqli',
     'username' => 'root',
     'password' => 'asqw',
     'database' => 'invoice']
);

$mapper = new \App\Model\Mapper\StandardMapper();

$entityFactory = new EntityFactory($container);

$_er = new EntityResuscitator($mapper, $connection, $entityFactory);

return $container;