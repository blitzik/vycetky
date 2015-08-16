<?php

use Tester\Assert;

require '../../bootstrap.php';

$listing = new App\Model\Entities\Listing(
    2012, 2, 1
);

Assert::false($listing->isActual());

Assert::same(29, $listing->getNumberOfDaysInMonth());

$l = new App\Model\Entities\Listing((int)date('Y'), (int)date('n'), 1);

Assert::true($l->isActual());