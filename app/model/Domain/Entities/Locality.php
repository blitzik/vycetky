<?php

namespace App\Model\Entities;
use Nette\Utils\Validators;

/**
 * @property-read int $localityID
 * @property string $name
 * @property-read ListingItem[] $items m:belongsToMany(localityID:listing_item)
 */
class Locality extends BaseEntity
{
    /**
     * @param string $localityName
     * @return Locality
     */
    public function __construct($localityName)
    {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setName($localityName);
    }

    /**
     * @param string $localityName
     */
    public function setName($localityName)
    {
        $localityName = trim($localityName);

        Validators::assert($localityName, 'string:1..40');
        $this->row->name = $localityName;
    }
}