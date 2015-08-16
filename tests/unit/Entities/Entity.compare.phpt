<?php

use Tester\Assert;

require '../../bootstrap.php';

$workedHours = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);

$workedHours2 = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);

Assert::same(true, $workedHours->compare($workedHours2));

//////////

$workedHours2 = new \App\Model\Entities\WorkedHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:30')
);

Assert::same(false, $workedHours->compare($workedHours2));

//////////

Assert::same(true, $workedHours->compare($workedHours2, ['lunch']));