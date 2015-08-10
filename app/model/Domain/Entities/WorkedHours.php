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
     * @param InvoiceTime $workStart
     * @param InvoiceTime $workEnd
     * @param InvoiceTime $lunch
     * @param InvoiceTime $otherHours
     * @throws ShiftEndBeforeStartException
     * @throws NegativeResultOfTimeCalcException
     */
    public function setHours(
        InvoiceTime $workStart,
        InvoiceTime $workEnd,
        InvoiceTime $lunch,
        InvoiceTime $otherHours = null
    ) {
        if ($workStart->compare($workEnd) === 1) {
            throw new ShiftEndBeforeStartException(
                'You cannot quit your shift before you even started!'
            );
        }

        if ($otherHours !== null) {
            $this->row->otherHours = $otherHours->getTime();
        }

        $this->row->workStart = $workStart->getTime();
        $this->row->workEnd = $workEnd->getTime();
        $this->row->lunch = $lunch->getTime();

        $this->row->hours = $this->getHours()->getTime();
        $this->row->totalWorkedHours = $this->getTotalWorkedHours()->getTime();
    }

    public function getHours()
    {
        return $this->workEnd->subTime($this->workStart)->subTime($this->lunch);
    }

    public function getTotalWorkedHours()
    {
        return $this->getHours()->sumWith($this->otherHours);
    }

}