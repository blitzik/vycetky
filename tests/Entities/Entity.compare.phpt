<?php

use Tester\Assert;

require '../bootstrap.php';

$workedHours = new \App\Model\Entities\WorkedHours();
$workedHours->setHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);

$workedHours2 = new \App\Model\Entities\WorkedHours();
$workedHours2->setHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);


Assert::same(true, $workedHours->compare($workedHours2));

//////////

$workedHours2->setHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:30')
);

Assert::same(false, $workedHours->compare($workedHours2));

//////////

Assert::same(false, $workedHours->compare($workedHours2, ['lunch']));

/////////

Assert::same(true, $workedHours->compare($workedHours2, ['lunch', 'hours', 'totalWorkedHours']));