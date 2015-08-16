<?php

namespace App\Model\Entities;

use Exceptions\Runtime\OtherHoursZeroTimeException;
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
     * @param int $day
     * @param Listing $listing
     * @param WorkedHours $workedHours
     * @param Locality $locality
     * @param string|null $description
     * @param string|null $descOtherHours
     * @return ListingItem
     */
    public function __construct(
        $day,
        Listing $listing,
        WorkedHours $workedHours,
        Locality $locality,
        $description = null,
        $descOtherHours = null
    ) {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setDay($day);
        $this->setListing($listing);
        $this->setLocality($locality);
        $this->setDescription($description);

        $this->setWorkedTime($workedHours, $descOtherHours);
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        Validators::assert($description, 'string:..30|null');
        $this->row->description = $description;
    }

    /**
     * @param string $descOtherHours
     * @param WorkedHours $workedHours
     * @throws OtherHoursZeroTimeException
     */
    public function setWorkedTime(WorkedHours $workedHours, $descOtherHours = null)
    {
        $this->setWorkedHours($workedHours);
        $this->setDescOtherHours($descOtherHours);
    }

    /**
     * @param string|null $descOtherHours
     * @throws OtherHoursZeroTimeException
     */
    private function setDescOtherHours($descOtherHours)
    {
        Validators::assert($descOtherHours, 'string:..30|null');

        if (!empty($descOtherHours) and isset($this->row->workedHoursID)) {
            if ($this->workedHours->otherHours->toSeconds() == 0) {
                throw new OtherHoursZeroTimeException;
            }
        }

        $this->row->descOtherHours = $descOtherHours;
    }

    /**
     * @param $day
     */
    public function setDay($day)
    {
        Validators::assert($day, 'numericint:1..31');

        if (!$this->isDetached()) {
            if (isset($this->listing)) {
                $daysInMonth = $this->listing->getNumberOfDaysInMonth();
                if ($day < 1 or $day > $daysInMonth) {
                    throw new InvalidArgumentException(
                        'Argument $day must be integer number
                         between 1-' . $daysInMonth
                    );
                }
            }
        }

        $this->row->day = $day;
    }

    /**
     * @param Listing $listing
     */
    public function setListing(Listing $listing)
    {
        $listing->checkEntityState();

        $listingDaysInMonth = $listing->getNumberOfDaysInMonth();
        if (isset($this->day) and $this->day > $listingDaysInMonth) {
            throw new InvalidArgumentException(
                'Day of ListingItem exceed last day in Listing period.'
            );
        }
        $this->assignEntityToProperty($listing, 'listing');
    }

    /**
     * @param WorkedHours $workedHours
     */
    private function setWorkedHours(WorkedHours $workedHours)
    {
        $workedHours->checkEntityState();
        $this->assignEntityToProperty($workedHours, 'workedHours');
    }

    /**
     * @param Locality $locality
     */
    public function setLocality(Locality $locality)
    {
        $locality->checkEntityState();
        $this->assignEntityToProperty($locality, 'locality');
    }
}