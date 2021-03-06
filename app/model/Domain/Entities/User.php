<?php

namespace App\Model\Entities;

use Nette\Security\Passwords;
use Nette\Utils\Validators;
use DateTime;

/**
 * @property-read int $userID
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string|null $name = null
 * @property string $role = 'employee'
 * @property-read string $ip
 * @property DateTime $lastLogin
 * @property string $lastIP
 * @property-read string|null $token = null
 * @property-read DateTime|null $tokenValidity = null
 */
class User extends BaseEntity
{
    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $ip
     * @param string $role
     * @param string|null $name
     * @return User
     */
    public function __construct(
        $username,
        $password,
        $email,
        $ip,
        $role = 'employee',
        $name = null
    ) {
        $this->row = \LeanMapper\Result::createDetachedInstance()->getRow();

        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
        $this->setIp($ip);
        $this->setRole($role);
        $this->setName($name);

        $this->setLastIP($ip);
        $this->setLastLogin(new DateTime());
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $username = trim($username);
        Validators::assert($username, 'unicode:1..25');
        $this->row->username = $username;
    }

    /**
     * Method also hashes password
     * @param string $password
     */
    public function setPassword($password)
    {
        $password = Passwords::hash(trim($password));
        $this->row->password = $password;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        Validators::assert($email, 'email');
        $this->row->email = $email;
    }

    /**
     * @param null|string $name
     */
    public function setName($name)
    {
        if (!is_null($name)) {
            $name = trim($name);
        }
        Validators::assert($name, 'unicode:..70|null');
        $this->row->name = $name;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $role = trim($role);
        Validators::assert($role, 'unicode:1..20');
        $this->row->role = $role;
    }

    /**
     * @param $ip
     * @return string|false
     */
    private function validateIPAddress($ip)
    {
        $ip = filter_var($ip, FILTER_VALIDATE_IP, ['flags' => [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6]]);

        return $ip;
    }

    /**
     * @param string $ip
     */
    private function setIp($ip)
    {
        $this->row->ip = $this->validateIPAddress($ip);
    }

    /**
     * @param DateTime $lastLogin
     */
    public function setLastLogin(DateTime $lastLogin)
    {
        $this->row->lastLogin = $lastLogin;
    }

    /**
     * @param string $lastIP
     */
    public function setLastIP($lastIP)
    {
        $this->row->lastIP = $this->validateIPAddress($lastIP);
    }

    public function createToken()
    {
        $this->row->token = \Nette\Utils\Random::generate(32);

        $currentDay = new \DateTime();
        $tokenValidity = $currentDay->modify('+1 day');

        $this->row->tokenValidity = $tokenValidity;
    }

    public function resetToken()
    {
        $this->row->token = null;
        $this->row->tokenValidity = null;
    }
}