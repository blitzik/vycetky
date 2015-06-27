<?php

namespace App\Model\Entities;

/**
 * @property-read int $localityID
 * @property string $name
 * @property-read ListingItem[] $items m:belongsToMany(localityID:listing_item)
 */
class Locality extends BaseEntity
{

}