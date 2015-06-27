<?php


class EntityFactory extends \Nette\Object implements \LeanMapper\IEntityFactory
{
    /**
     * @var \Nette\DI\Container
     */
    private $container;

    public function __construct(\Nette\DI\Container $container)
    {
        $this->container = $container;
    }

    /**
     * Creates entity instance from given entity class name and argument
     *
     * @param string $entityClass
     * @param \LeanMapper\Row|Traversable|array|null $arg
     * @return \LeanMapper\Entity
     */
    public function createEntity($entityClass, $arg = null)
    {
        $entity = new $entityClass();/*$this->container->createInstance($entityClass);*/
        //$this->container->callInjects($entity);
        $entity->loadState($arg);

        return $entity;
    }

    /**
     * Allows wrap set of entities in custom collection
     *
     * @param \LeanMapper\Entity[] $entities
     * @return mixed
     */
    public function createCollection(array $entities)
    {
        return $entities;
    }

}