<?php

use Tester\Assert;

require '../bootstrap.php';

$listing = new \App\Model\Entities\Listing();
$listing->setPeriod(2012, 2);

Assert::exception(function () use ($listing) {

    $listing->setPeriod(2015, 6);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Listing "period" has been already set.');

Assert::false($listing->isActual());

Assert::same(29, $listing->getNumberOfDaysInMonth());

$l = new \App\Model\Entities\Listing();
$l->setPeriod((int)date('Y'), (int)date('n'));

Assert::true($l->isActual());

