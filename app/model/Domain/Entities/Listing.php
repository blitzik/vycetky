<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Time\TimeUtils;
use Nette\Utils\Validators;
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
     * @param User|int $user
     * @param string|null $description
     * @param string|null $hourlyWage
     * @return Listing
     */
    public function __construct(
        $year,
        $month,
        $user,
        $description = null,
        $hourlyWage = null
    ) {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setListingPeriod($year, $month);
        $this->setUser($user);
        $this->setDescription($description);
        $this->setHourlyWage($hourlyWage);
    }

    /**
     * @param $user
     */
    public function setUser($user)
    {
        if ($user instanceof User and !$user->isDetached()) {
            $this->assignEntityToProperty($user, 'user');
        } else if (Validators::is($user, 'numericint')) {
            $this->row->userID = $user;
            $this->row->cleanReferencedRowsCache('user', 'userID');
        } else {
            throw new InvalidArgumentException(
                'Argument $user can by only attached instance
                 of App\Entities\User or integer number.'
            );
        }
    }

    /**
     * @param int $year
     * @param int $month
     */
    private function setListingPeriod($year, $month)
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

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $description = trim($description);
        Validators::assert($description, 'string:..40|null');
        $this->row->description = $description;
    }

    /**
     * @param int|null $hourlyWage
     */
    public function setHourlyWage($hourlyWage)
    {
        Validators::assert($hourlyWage, 'none|numericint:0..|null');
        if (empty($hourlyWage)) {
            $hourlyWage = null;
        }

        $this->row->hourlyWage = $hourlyWage;
    }

    /**
     * @return bool
     */
    public function isActual()
    {
        if ($this->getPeriod()->format('Y-m') == (new \DateTime())->format('Y-m'))
            return true;

        return false;
    }

    /**
     * @return bool|DateTime
     */
    public function getPeriod()
    {
        return TimeUtils::getDateTimeFromParameters(
            $this->row->year,
            $this->row->month
        );
    }

    /**
     * @return int
     */
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

    /**
     * @return string
     */
    public function entireDescription()
    {
        $desc = TimeUtils::getMonthName($this->row->month) . ' ' . $this->row->year;
        if (isset($this->row->description)) { // != intentionally
            $desc .= ' - '.$this->row->description;
        }/* else {
            $desc .= ' - Bez popisu';
        }*/

        return $desc;
    }

    /**
     * @return mixed
     */
    public function getOwnerID()
    {
        return $this->row->userID;
    }

    /**
     * @return InvoiceTime
     */
    public function getLunchHours()
    {
        $this->checkEntityState();
        return $this->toInvoiceTime($this->row->lunchHours);
    }

    /**
     * @return InvoiceTime
     */
    public function getOtherHours()
    {
        $this->checkEntityState();
        return $this->toInvoiceTime($this->row->otherHours);
    }

    /**
     * @return InvoiceTime
     */
    public function getWorkedHours()
    {
        $this->checkEntityState();
        return $this->toInvoiceTime($this->row->workedHours);
    }

    /**
     * @return InvoiceTime
     */
    public function getTotalWorkedHours()
    {
        $this->checkEntityState();
        return $this->toInvoiceTime($this->row->totalWorkedHours);
    }

    /**
     * @return int|null
     */
    public function getWorkedDays()
    {
        $this->checkEntityState();
        return $this->row->workedDays;
    }



}