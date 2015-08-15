<?php

use Tester\Assert;

require '../../bootstrap.php';

$listing = App\Model\Entities\Listing::loadState(
    2012, 2, 1
);

/*Assert::exception(function () use ($listing) {

    $listing->loadState(2015, 6, 1);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Listing "period" has been already set.');*/

Assert::false($listing->isActual());

Assert::same(29, $listing->getNumberOfDaysInMonth());

$l = \App\Model\Entities\Listing::loadState((int)date('Y'), (int)date('n'), 1);

Assert::true($l->isActual());