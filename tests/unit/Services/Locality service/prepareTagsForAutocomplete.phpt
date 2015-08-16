<?php

use Tester\Assert;

require '../../../bootstrap.php';

$service = new \App\Model\Services\LocalityService();

$localitiesNames = ['Praha', 'Brno', 'Ostrava'];

$localities = [];
foreach ($localitiesNames as $localityName) {
    $locality = new \App\Model\Entities\Locality($localityName);

    $localities[] = $locality;
}

$result = $service->prepareTagsForAutocomplete($localities);

Assert::same($localitiesNames, $result);

Assert::exception(function () use ($service, $localities) {

    array_unshift($localities, new \App\Model\Entities\Listing(2015, 5, 1));

    $service->prepareTagsForAutocomplete($localities);

}, 'Exceptions\Logic\InvalidArgumentException',
   'Function parameter can only consist of Locality Entities.');