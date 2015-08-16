<?php

use Tester\Assert;

require '../../bootstrap.php';

$workedHours = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00'),
    new InvoiceTime('01:00')
);

Assert::same('09:00:00', $workedHours->getHours()->getTime());

Assert::same('10:00:00', $workedHours->getTotalWorkedHours()->getTime());

Assert::exception(function () {
    $workedHours = new \App\Model\Entities\WorkedHours(
        new InvoiceTime('16:00'),
        new InvoiceTime('06:00'),
        new InvoiceTime('01:00'),
        new InvoiceTime('01:00')
    );
}, 'Exceptions\Runtime\ShiftEndBeforeStartException',
   'You cannot quit your shift before you even started!');