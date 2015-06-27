<?php

namespace App\Model\Entities;

use DateTime;

/**
 * @property-read int $userID
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string|null $name
 * @property string $role
 * @property string $ip
 * @property DateTime $lastLogin
 * @property string $lastIP
 * @property string|null $token
 * @property DateTime|null $tokenValidity
 */
class User extends BaseEntity
{

}