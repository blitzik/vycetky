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

    /**
     * @param \DateTime|InvoiceTime|int|string|null $workStart
     * @param \DateTime|InvoiceTime|int|string|null $workEnd
     * @param \DateTime|InvoiceTime|int|string|null $lunch
     * @param \DateTime|InvoiceTime|int|string|null null $otherHours
     */
    public function __construct(
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

        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->row->workStart = $workStart->getTime();
        $this->row->workEnd = $workEnd->getTime();
        $this->row->lunch = $lunch->getTime();
        $this->row->otherHours = $otherHours->getTime();
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