<?php

namespace App\Model\Repositories;

class InvitationRepository extends BaseRepository
{

    /**
     *
     * @param string $email
     * @return \App\Model\Entities\Invitation|FALSE
     */
    public function getInvitation($email)
    {
        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('email = ?', $email)
                                   ->fetch();
        if ($result == FALSE) {
            return FALSE;
        }

        return $this->createEntity($result);
    }

    /**
     *
     * @param string $token
     * @return \App\Model\Entities\Invitation
     * @throws \Exceptions\Runtime\TokenNotFoundException
     */
    public function checkToken($token)
    {
        $result = $this->connection->select('*')
                                   ->from($this->getTable())
                                   ->where('regHash = ?', $token)
                                   ->fetch();
        if ($result == FALSE) {
            throw new \Exceptions\Runtime\TokenNotFoundException;
        }

        return $this->createEntity($result);
    }

}