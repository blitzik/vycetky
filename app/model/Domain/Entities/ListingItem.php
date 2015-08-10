<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;

/**
 * @property-read int $listingItemID
 * @property int $day
 * @property Listing $listing m:hasOne(listingID:listing)
 * @property Locality $locality m:hasOne(localityID:locality)
 * @property WorkedHours $workedHours m:hasOne(workedHoursID:worked_hours)
 * @property string|null $description
 * @property string|null $descOtherHours
 */
class ListingItem extends BaseEntity
{
    /**
     * @return mixed
     */
    public function getListingID()
    {
        return $this->row->listingID;
    }

    /**
     * @param $day
     */
    public function setDay($day)
    {
        $errMessage = 'Argument $day must be integer number';
        if (!Validators::is($day, 'numericint')) {
            throw new InvalidArgumentException($errMessage);
        }

        if (!$this->isDetached()) {
            if (isset($this->listing)) {
                $daysInMonth = $this->listing->getNumberOfDaysInMonth();
                if ($day < 1 or $day > $daysInMonth) {
                    throw new InvalidArgumentException(
                        $errMessage . ' between 1-' . $daysInMonth
                    );
                }
            }
        } else {
            if ($day < 1 or $day > 31 ) {
                throw new InvalidArgumentException(
                    $errMessage . 'between 1-31'
                );
            }
        }

        $this->row->day = $day;
    }

    /**
     * @param Listing $listing
     */
    public function setListing(Listing $listing)
    {
        $listingDaysInMonth = $listing->getNumberOfDaysInMonth();
        if (isset($this->day) and $this->day > $listingDaysInMonth) {
            throw new InvalidArgumentException(
                'Day of ListingItem exceed last day in Listing period.'
            );
        }
        $this->assignEntityToProperty($listing, 'listing');
    }
}