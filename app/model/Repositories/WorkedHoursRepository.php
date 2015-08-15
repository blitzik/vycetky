<?php

namespace App\Model\Repositories;

use App\Model\Entities\WorkedHours;
use Tracy\Debugger;

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

    /**
     * @param WorkedHours $workedHours
     * @return WorkedHours
     * @throws \DibiException
     */
    public function setupWorkedHours(WorkedHours $workedHours)
    {
        if (empty($workedHours->getModifiedRowData())) {
            return $workedHours;
        }

        try {
            $this->persist($workedHours);
            return $workedHours;
        } catch (\DibiException $e) {
            if ($e->getCode() === 1062) {
                $val = $this->connection
                            ->select('workedHoursID AS id')
                            ->from($this->getTable())
                            ->where('%and', $workedHours->getRowData())
                            ->fetch();

                $workedHours->makeAlive($this->entityFactory, $this->connection, $this->mapper);
                $workedHours->attach($val['id']);

                return $workedHours;
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

}