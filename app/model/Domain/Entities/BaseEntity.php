<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use LeanMapper\Entity;

abstract class BaseEntity extends Entity
{
    public function loadState($args = null)
    {
        parent::__construct($args);
    }

    /**
     * Umožňuje předat entitě místo navázaných entit jejich 'id'
     * @Author Shaman
     *
     * @param string $name
     * @param mixed $value
     * @throws \LeanMapper\Exception\InvalidMethodCallException
     * @throws \LeanMapper\Exception\MemberAccessException
     */
    public function __set($name, $value)
    {
        $property = $this->getCurrentReflection()->getEntityProperty($name);
        //dump($name);
        //dump($value);
        //dump($property);
        if ($property->hasRelationship() && !($value instanceof Entity)) {
            $relationship = $property->getRelationship();
            $this->row->{$property->getColumn()} = $value;
            $this->row->cleanReferencedRowsCache(
                $relationship->getTargetTable(),
                $relationship->getColumnReferencingTargetTable()
            );
        } else {
            parent::__set($name, $value);
        }
    }

    public function excludeTemporaryFields()
    {
        $properties = $this->getCurrentReflection()->getEntityProperties();
        foreach ($properties as $name => $property) {
            if ($property->hasCustomFlag('temporary')) {
                unset($this->row->{$name});
            }
        }
    }

    /**
     * @param Entity $entity
     * @param array $excludedFields
     * @return bool
     */
    public function compare(Entity $entity, array $excludedFields = null)
    {
        if (!$entity instanceof $this) {
            throw new InvalidArgumentException(
                'Argument $entity has wrong instance type.'
            );
        }

        $_this = $this->getRowData();
        $e = $entity->getRowData();

        if (isset($excludedFields)) {
            $excludedFields = array_flip($excludedFields);
            foreach ($excludedFields as $fieldName => $v) {
                if (array_key_exists($fieldName, $_this)) {
                    unset($_this[$fieldName]);
                }
                if (array_key_exists($fieldName, $e)) {
                    unset($e[$fieldName]);
                }
            }
        }

        return md5(json_encode($_this)) === md5(json_encode($e));
    }

    /**
     * Při kolonování existující entity(v databází = attached)
     * bude entita stejná, kromě ID. Pokud má být ID zachováno,
     * na existující entitě před klonováním zavoláme ->detach().
     */
    public function __clone()
    {
        if (!$this->row->isDetached()) {
            $this->row = clone $this->row;

            $primaryKey = $this->mapper
                               ->getPrimaryKey(
                                   $this->getReflection()
                                        ->getShortName()
                               );
            $this->row->detach();

            unset($this->row->{$primaryKey});
        }

        $this->mapper = null;
    }

}