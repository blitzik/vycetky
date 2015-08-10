<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Time\TimeUtils;
use \InvoiceTime;
use DateTime;

/**
 * @property-read int $listingID
 * @property User $user m:hasOne(userID:user)
 * @property-read int $year
 * @property-read int $month
 * @property-read \DateTime $period m:temporary
 * @property string|null $description
 * @property int|null $hourlyWage
 * @property-read ListingItem[] $listingItems m:belongsToMany(listingID:listing_item)
 * @property-read InvoiceTime $lunchHours m:temporary m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $otherHours m:temporary m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $workedHours m:temporary m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $totalWorkedHours m:temporary m:passThru(toInvoiceTime|)
 * @property-read int|null $workedDays m:temporary
 */
class Listing extends BaseEntity
{
    use TInvoiceTimeConversion;

    private $numberOfDaysInListingMonth;

    /**
     * @param int $year
     * @param int $month
     */
    public function setPeriod($year, $month)
    {
        if (!is_int($year) or !is_int($month)) {
            throw new InvalidArgumentException(
                'Both arguments must be integer numbers'
            );
        }

        if (isset($this->row->year) or isset($this->row->month)) {
            throw new InvalidArgumentException(
                'Listing "period" has been already set.'
            );
        }

        $this->row->year = $year;
        $this->row->month = $month;
    }

    public function isActual()
    {
        if ($this->getPeriod()->format('Y-m') == (new \DateTime())->format('Y-m'))
            return true;

        return false;
    }

    public function getItems()
    {
        return $this->listingItems;
    }

    public function getPeriod()
    {
        return TimeUtils::getDateTimeFromParameters(
            $this->row->year,
            $this->row->month
        );
    }

    public function getNumberOfDaysInMonth()
    {
        if (!isset($this->numberOfDaysInListingMonth)) {
            $this->numberOfDaysInListingMonth = TimeUtils::getNumberOfDaysInMonth(
                $this->period->format('Y'),
                $this->period->format('n')
            );
        }

        return $this->numberOfDaysInListingMonth;
    }

    public function entireDescription()
    {
        $desc = TimeUtils::getMonthName($this->month) . ' ' . $this->year;
        if ($this->description != null) { // != intentionally
            $desc .= ' - '.$this->description;
        }/* else {
            $desc .= ' - Bez popisu';
        }*/

        return $desc;
    }

    public function getOwnerID()
    {
        return $this->row->userID;
    }

}