<?php

namespace App\Model\Domain;

use App\Model\Entities\ListingItem;
use App\Model\Time\TimeManipulator;
use App\Model\Time\TimeUtils;
use Nette\Object;

class ListingItemDecorator extends Object
{

    /**
     * @var ListingItem
     */
    private $listingItem;

    private $year;
    private $month;


    public function __construct(
        ListingItem $listingItem,
        $year,
        $month
    ) {
        $this->listingItem = $listingItem;
        $this->year = $year;
        $this->month = $month;
    }

    public function isFilling()
    {
        return ($this->listingItem->isDetached()) ? true : false;
    }

    public function getListingItemID()
    {
        return $this->listingItem->listingItemID;
    }

    public function getLocality()
    {
        return $this->listingItem->locality->name;
    }

    public function getDay()
    {
        return TimeUtils::getDateTimeFromParameters(
            $this->year,
            $this->month,
            $this->listingItem->day
        );
    }

    public function isWeekDay()
    {
        $d = date_format($this->getDay(), 'w');

        return ($d > 0 && $d < 6) ? true : false;
    }

    public function isCurrentDay()
    {
        if ($this->getDay()->format('Y-m-d') == (new \DateTime('now'))->format('Y-m-d'))
            return true;

        return false;
    }

    public function getWorkStart()
    {
        return $this->listingItem->workedHours->workStart;
    }

    public function getWorkEnd()
    {
        return $this->listingItem->workedHours->workEnd;
    }

    public function getLunch()
    {
        return $this->listingItem->workedHours->lunch;
    }

    public function getHours()
    {
        return $this->listingItem->workedHours->hours;
    }

    public function getOtherHours()
    {
        return $this->listingItem->workedHours->otherHours;
    }

    public function getDescOtherHours()
    {
        return $this->listingItem->descOtherHours;
    }

    public function getDescription()
    {
        return $this->listingItem->description;
    }

    public function getListingID()
    {
        return $this->listingItem->getListingID();
    }

    public function getListingItem()
    {
        return $this->listingItem;
    }

    /**
     * @return bool
     */
    public function areWorkedHoursWithoutLunchZero()
    {
        $workedHours = TimeManipulator::subTimes(
                           [$this->listingItem->workedHours->workStart,
                            $this->listingItem->workedHours->workEnd]
                       );

        return ($workedHours === '00:00:00') ? true : false;
    }

}