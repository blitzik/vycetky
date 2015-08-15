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
    public static function loadState($localityName)
    {
        $locality = new self;
        $locality->setName($localityName);

        return $locality;
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