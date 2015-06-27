<?php

namespace App\Model\Entities;

use DateTime;

/**
 * @property-read int $invitationID
 * @property string $email
 * @property string $regHash
 * @property \DateTime $validity
 */
class Invitation extends BaseEntity
{

}