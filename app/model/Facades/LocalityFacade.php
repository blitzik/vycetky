<?php

namespace App\Model\Facades;

use App\Model\Repositories\LocalityRepository;
use App\Model\Services\LocalityService;
use Nette\Utils\Validators;
use Nette\Security\User;

class LocalityFacade extends BaseFacade
{
    /**
     * @var LocalityRepository
     */
    private $localityRepository;

    /**
     * @var LocalityService
     */
    private $localityService;

    public function __construct(
        LocalityRepository $localityRepository,
        LocalityService $localityService,
        User $user
    ) {
        parent::__construct($user);

        $this->localityRepository = $localityRepository;
        $this->localityService = $localityService;
        $this->user = $user;
    }

    /**
     *
     * @param string $localityName
     * @param int $limit
     * @param User|int|null $user
     * @return string Localities
     */
    public function findLocalitiesForAutocomplete(
        $localityName,
        $limit,
        $user = null
    ) {
        Validators::assert($localityName, 'string');

        $localities = $this->findLocalities($localityName, $limit, $user);

        return $this->localityService->prepareTagsForAutocomplete($localities);
    }

    /**
     * @param string|null $localityName
     * @param int $limit
     * @param User|int|null $user
     * @return array
     */
    public function findLocalities($localityName, $limit, $user = null)
    {
        Validators::assert($localityName, 'unicode|null');
        Validators::assert($limit, 'numericint');
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $localities = $this->localityRepository
                                  ->findSimilarByName(
                                      $localityName,
                                      $userID,
                                      $limit
                                  );
    }

    /**
     * @return int
     */
    public function getNumberOfUserLocalities()
    {
        return $this->localityRepository->getNumberOfUserLocalities($this->user->id);
    }

    /**
     * @param User|int|null $user
     * @return array
     */
    public function findAllUserLocalities($user)
    {
        $userID = $this->getIdOfSignedInUserOnNull($user);

        return $this->localityRepository->findAllUserLocalities($userID);
    }

    /**
     * @param int $localityID
     */
    public function removeUserLocality($localityID)
    {
        Validators::assert($localityID, 'numericint');

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