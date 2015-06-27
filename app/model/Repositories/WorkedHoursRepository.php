<?php

namespace App\Model\Repositories;

class WorkedHoursRepository extends BaseRepository
{

    public function getByValues(array $conditions)
    {
        $result = $this->connection->select('*')
                       ->from($this->getTable())
                       ->where('%and', $conditions)
                       ->fetch();

        if ($result == FALSE)
            throw new \Exceptions\Runtime\WorkedHoursNotFoundException;

        return $this->createEntity($result);
    }

    public function getTotalWorkedStatistics($userID)
    {
        $result = $this->connection->query(
            'SELECT SUM(time_to_sec(ADDTIME(
                        SUBTIME(SUBTIME(wh.workEnd, wh.workStart), wh.lunch),
                        wh.otherHours))
                    ) as workedHours,
                    COUNT(li.listingItemID) AS workedDays
             FROM listing l
             INNER JOIN listing_item li ON (l.listingID = li.listingID)
             INNER JOIN worked_hours wh ON (wh.workedHoursID = li.workedHoursID)
             WHERE l.userID = ?', $userID, 'GROUP BY l.userID'
        )->fetch();

        return $result;
    }

}