<?php

namespace App\Model\Repositories;

use App\Model\Entities\Locality;

class LocalityRepository extends BaseRepository
{
    /**
     * @param $id
     * @return mixed
     */
    public function findById($id)
    {
        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('localityID = ?', $id)
                                   ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\LocalityNotFoundException;

        return $this->createEntity($result);
    }

    /**
     * @param $localityName
     * @return mixed
     */
    public function findByName($localityName)
    {
        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('name = ?', $localityName)
                                   ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\LocalityNotFoundException;

        return $this->createEntity($result);

    }

    /**
     * @param $localityName
     * @param $userID
     * @return array
     */
    public function findSimilarByName($localityName, $userID, $limit)
    {
        $results = $this->connection->select('l.localityID, l.name')
                        ->from($this->getTable())->as('l')
                        ->innerJoin('locality_user lu
                                     ON (lu.localityID = l.localityID)')
                        ->where('l.name LIKE %~like~ COLLATE utf8_czech_ci', $localityName)
                        ->where('lu.userID = ?', $userID)
                        ->limit($limit)
                        ->fetchAll();

        return $this->createEntities($results);
    }

    public function getNumberOfUserLocalities($userID)
    {
        $result = $this->connection
                       ->select('COUNT(localityUserID) as count')
                       ->from('locality_user')
                       ->where('userID = ?', $userID)
                       ->orderBy('localityUserID')
                       ->fetch();

        return $result['count'];
    }

    /**
     * @return array
     */
    public function findAll()
    {
        return $this->createEntities($this->connection->select('*')
                                          ->from($this->getTable())
                                          ->fetchAll());
    }

    /**
     * @param $userID
     * @return array
     */
    public function findAllUserLocalities($userID)
    {
        $results = $this->connection->select('l.localityID, l.name')
                        ->from($this->getTable())->as('l')
                        ->innerJoin('locality_user lu
                                     ON (lu.localityID = l.localityID)')
                        ->where('lu.userID = ?', $userID)
                        //->orderBy('l.name')
                        ->fetchAll();

        return $this->createEntities($results);
    }

    /**
     * @param $localityID
     * @param $userID
     */
    public function removeUserLocality($localityID, $userID)
    {
        $this->connection->delete('locality_user')
                         ->where('localityID = ? AND userID = ?',
                                 $localityID, $userID)->execute();
    }

    public function removeLocalities(array $localitiesIDs, $userID)
    {
        $this->connection->delete('locality_user')
                         ->where('userID = ?', $userID)
                         ->where('localityID IN (?)', $localitiesIDs)
                         ->execute();
    }

    /**
     * @param Locality $locality
     * @param $userID
     */
    public function saveLocalityToUserList(Locality $locality, $userID)
    {
        $this->connection
             ->query('INSERT IGNORE INTO locality_user',
                 ['localityID' => $locality->localityID,
                  'userID' => $userID]
             );
    }

}