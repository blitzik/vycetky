<?php

require '../bootstrap.php';

use Tester\Assert;

$wh = new \App\Model\Entities\WorkedHours();
$wh->setHours(
    new InvoiceTime('06:00'),
    new InvoiceTime('16:00'),
    new InvoiceTime('01:00')
);

Assert::same(true, $wh->isDetached());

$wh->makeAlive($entityFactory, $connection, $mapper);
$wh->attach(1);

Assert::same(false, $wh->isDetached());

$wh2 = clone $wh;

Assert::same(true, $wh2->isDetached());
Assert::same($wh->workStart->getTime(), $wh2->workStart->getTime());
Assert::same($wh->workEnd->getTime(), $wh2->workEnd->getTime());
Assert::same($wh->lunch->getTime(), $wh2->lunch->getTime());