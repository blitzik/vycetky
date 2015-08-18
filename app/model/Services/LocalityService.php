<?php

namespace App\Model\Services;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\Locality;
use Nette\Object;

class LocalityService extends Object
{
    /**
     * @param array $localities
     * @return string
     */
    public function prepareTagsForAutocomplete(array $localities)
    {
        $tags = [];
        foreach ($localities as $locality) {
            if (!($locality instanceof Locality))
                throw new InvalidArgumentException(
                    'Function argument $localities can only consist of '.Locality::class.' Entities.'
                );

            $tags[] = $locality->name;
        }
        return $tags;
    }
}