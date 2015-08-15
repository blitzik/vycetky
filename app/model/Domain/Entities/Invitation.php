<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use DateTime;

/**
 * @property-read int $invitationID
 * @property string $email
 * @property string $regHash
 * @property \DateTime $validity
 */
class Invitation extends BaseEntity
{
    /**
     * @param string $email
     * @param string $regHash
     * @param DateTime $validity
     * @return Invitation
     */
    public static function loadState(
        $email,
        $regHash,
        DateTime $validity
    ) {
        /*$this->setEmail($email);
        $this->setRegHash($regHash);
        $this->setValidity($validity);*/

        $invitation = new self;
        $invitation->setEmail($email);
        $invitation->setRegHash($regHash);
        $invitation->setValidity($validity);

        return $invitation;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $email = trim($email);
        Validators::assert($email, 'email');

        $this->row->email = $email;
    }

    /**
     * @param string $regHash
     */
    public function setRegHash($regHash)
    {
        $regHash = trim($regHash);
        Validators::assert($regHash, 'string:32');

        $this->row->regHash = $regHash;
    }

    /**
     * @param DateTime $validity
     */
    public function setValidity(DateTime $validity)
    {
        if ($validity <= (new DateTime())) {
            throw new InvalidArgumentException(
                'You cannot set $validity to the past time. Check your
                 DateTime value.'
            );
        }

        $this->row->validity = $validity;
    }
}