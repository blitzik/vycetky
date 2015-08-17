<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use Nette\Utils\Random;
use DateTime;

/**
 * @property-read int $invitationID
 * @property string $email
 * @property-read string $token
 * @property \DateTime $validity
 */
class Invitation extends BaseEntity
{
    /**
     * @param string $email
     * @param DateTime $validity
     * @return Invitation
     */
    public function __construct(
        $email,
        DateTime $validity
    ) {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setEmail($email);
        $this->setValidity($validity);

        $this->generateToken();
    }

    private function generateToken()
    {
        $this->row->token = Random::generate(32);
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