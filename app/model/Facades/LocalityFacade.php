<?php

namespace App\Model\Facades;

use App\Model\Repositories\LocalityRepository;
use App\Model\Services\LocalityService;
use Nette\Security\User;
use Nette\Object;

class LocalityFacade extends Object
{
    /**
     * @var LocalityRepository
     */
    private $localityRepository;

    /**
     * @var LocalityService
     */
    private $localityService;

    /**
     * @var User
     */
    private $user;


    public function __construct(
        LocalityRepository $localityRepository,
        LocalityService $localityService,
        User $user
    ) {
        $this->localityRepository = $localityRepository;
        $this->localityService = $localityService;
        $this->user = $user;
    }

    /**
     *
     * @param string $localityName
     * @return string Localities separated by comma or empty string
     */
    public function findLocalitiesForAutocomplete($localityName, $limit)
    {
        $localities = $this->localityRepository
                           ->findSimilarByName($localityName, $this->user->id, $limit);

        return $this->localityService->prepareTagsForAutocomplete($localities);
    }

    public function findLocalities($localityName, $limit)
    {
        return $localities = $this->localityRepository
                                  ->findSimilarByName(
                                      $localityName,
                                      $this->user->id,
                                      $limit
                                  );
    }

    public function getNumberOfUserLocalities()
    {
        return $this->localityRepository->getNumberOfUserLocalities($this->user->id);
    }

    /**
     * @param $userID
     * @return array
     */
    public function findAllUserLocalities($userID)
    {
        return $this->localityRepository->findAllUserLocalities($userID);
    }

    /**
     * @param $localityID
     */
    public function removeUserLocality($localityID)
    {
        $this->localityRepository->removeUserLocality($localityID, $this->user->id);
    }

    /**
     * @param array $localitiesIDs
     */
    public function removeLocalities(array $localitiesIDs)
    {
        $this->localityRepository->removeLocalities($localitiesIDs, $this->user->id);
    }
}