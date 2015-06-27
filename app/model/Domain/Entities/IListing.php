<?php

namespace App\Model\Entities;

interface IListing
{
    /**
     * @return \DateTime
     */
    public function getPeriod();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @return ListingItem[]
     */
    public function getListingItems();

    /**
     * @return string Format HH:MM:SS
     */
    public function getLunchHours();

    /**
     * @return string Format HH:MM:SS
     */
    public function getOtherHours();

    /**
     * @return string Format HH:MM:SS
     */
    public function getWorkedHours();

    /**
     * @return int Total worked hours in seconds
     */
    public function getTotalWorkedHours();

    /**
     * @return int
     */
    public function getWorkedDays();

}