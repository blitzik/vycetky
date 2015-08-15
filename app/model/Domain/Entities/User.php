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
 * @property string|null $name
 * @property string $role
 * @property-read string $ip
 * @property DateTime $lastLogin
 * @property string $lastIP
 * @property string|null $token
 * @property DateTime|null $tokenValidity
 */
class User extends BaseEntity
{
    /**
     * Allows initialize properties' default values
     */
    protected function initDefaults()
    {
        parent::initDefaults();

        $this->row->role = 'employee';
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $email
     * @param string $ip
     * @param string $role
     * @param string|null $name
     * @return User
     */
    public static function loadState(
        $username,
        $password,
        $email,
        $ip,
        $role = 'employee',
        $name = null
    ) {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setEmail($email);
        $user->setIp($ip);
        $user->setRole($role);
        $user->setName($name);

        $user->setLastIP($ip);
        $user->setLastLogin(new DateTime());

        return $user;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $username = trim($username);
        Validators::assert($username, 'string:1..25');
        $this->row->username = $username;
    }

    /**
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
        Validators::assert($name, 'string:..70|null');
        $this->row->name = $name;
    }

    /**
     * @param string $role
     */
    public function setRole($role)
    {
        $role = trim($role);
        Validators::assert($role, 'string:1..20');
        $this->row->role = $role;
    }

    /**
     * @param $ip
     * @return string|false
     */
    private function validateIPAddress($ip)
    {
        $ip = filter_var($ip, FILTER_VALIDATE_IP, ['flags' => [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6]]);
        Validators::assert($ip, 'string:1..30');

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

    /**
     * @param null|string $token
     */
    public function setToken($token)
    {
        if (!is_null($token)) {
            $token = trim($token);
        }
        Validators::assert($token, 'string:32');
        $this->row->token = $token;
    }

    /**
     * @param DateTime|null $tokenValidity
     */
    public function setTokenValidity($tokenValidity = null)
    {
        $this->row->tokenValidity = $tokenValidity;
    }

}