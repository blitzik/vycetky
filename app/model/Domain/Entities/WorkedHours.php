<?php

namespace App\Model\Entities;

use Exceptions\Runtime\NegativeResultOfTimeCalcException;
use Exceptions\Runtime\ShiftEndBeforeStartException;
use InvoiceTime;

/**
 * @property-read int $workedHoursID
 * @property-read InvoiceTime $workStart m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $workEnd m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $lunch m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $otherHours m:passThru(toInvoiceTime|)
 * @property-read InvoiceTime $hours m:temporary
 * @property-read InvoiceTime $totalWorkedHours m:temporary
 */
class WorkedHours extends BaseEntity
{
    use TInvoiceTimeConversion;

    protected function initDefaults()
    {
        parent::initDefaults();

        $this->row->otherHours = '00:00:00';
    }

    /**
     * @param \DateTime|InvoiceTime|int|string|null $workStart
     * @param \DateTime|InvoiceTime|int|string|null $workEnd
     * @param \DateTime|InvoiceTime|int|string|null $lunch
     * @param \DateTime|InvoiceTime|int|string|null $otherHours
     * @throws ShiftEndBeforeStartException
     * @throws NegativeResultOfTimeCalcException
     * @return WorkedHours
     */
    public static function loadState(
        $workStart,
        $workEnd,
        $lunch,
        $otherHours = null
    ) {
        $workStart = new InvoiceTime($workStart);
        $workEnd = new InvoiceTime($workEnd);
        $lunch = new InvoiceTime($lunch);
        $otherHours = new InvoiceTime($otherHours);

        if ($workStart->compare($workEnd) === 1) {
            throw new ShiftEndBeforeStartException(
                'You cannot quit your shift before you even started!'
            );
        }

        $wh = new WorkedHours();
        $wh->setWorkStart($workStart);
        $wh->setWorkEnd($workEnd);
        $wh->setLunch($lunch);
        $wh->setOtherHours($otherHours);

        return $wh;
    }

    /**
     * @param \DateTime|InvoiceTime|int|string|null $workStart
     */
    private function setWorkStart($workStart)
    {
        $this->row->workStart = InvoiceTime::processTime($workStart);
    }

    /**
     * @param \DateTime|InvoiceTime|int|string|null $workEnd
     */
    private function setWorkEnd($workEnd)
    {
        $this->row->workEnd = InvoiceTime::processTime($workEnd);
    }

    /**
     * @param \DateTime|InvoiceTime|int|string|null $lunch
     */
    private function setLunch($lunch)
    {
        $this->row->lunch = InvoiceTime::processTime($lunch);
    }

    /**
     * @param \DateTime|InvoiceTime|int|string|null $otherHours
     */
    private function setOtherHours($otherHours)
    {
        $this->row->otherHours = InvoiceTime::processTime($otherHours);
    }

    private function caclHours()
    {
        return $this->workEnd->subTime($this->workStart)->subTime($this->lunch);
    }

    /**
     * @return InvoiceTime
     */
    public function getHours()
    {
        if (!isset($this->row->hours)) {
            $this->row->hours = $this->caclHours()->getTime();
        }

        return $this->toInvoiceTime($this->row->hours);
    }

    /**
     * @return InvoiceTime
     */
    public function getTotalWorkedHours()
    {
        if (!isset($this->row->totalWorkedHours)) {
            $this->row->totalWorkedHours = $this->getHours()->sumWith($this->otherHours)->getTime();
        }
        return $this->toInvoiceTime($this->row->totalWorkedHours);
    }

}