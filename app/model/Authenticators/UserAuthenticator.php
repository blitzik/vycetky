<?php

namespace App\Model\Authenticators;

use Nette\Security\AuthenticationException;
use App\Model\Facades\UserManager;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\Identity;
use Nette\Http\IRequest;
use Nette\Object;

class UserAuthenticator extends Object implements IAuthenticator
{
    /**
     * @var IRequest
     */
    private $httpRequest;

    /**
     *
     * @var UserManager
     */
    private $userManager;


    public function __construct(
        UserManager $userManager,
        IRequest $httpRequest
    ) {
        $this->userManager = $userManager;
        $this->httpRequest = $httpRequest;
    }

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($email, $password) = $credentials;

        try {
            $user = $this->userManager->findUserByEmail($email);

        } catch (\Exceptions\Runtime\UserNotFoundException $u) {
            throw new AuthenticationException('Zadali jste špatný email.');
        }

        if (!Passwords::verify($password, $user->password)) {
            throw new AuthenticationException('Zadali jste špatné heslo.');

        } elseif (Passwords::needsRehash($user->password)) {

            $user->password = Passwords::hash($password);
            $this->userManager->saveUser($user);
        }

            $info = array('lastLogin' => new \DateTime(),
                          'lastIP'    => $this->httpRequest->getRemoteAddress());
            $user->assign($info);

            $this->userManager->saveUser($user);

            $arr = $user->getData();
            unset($arr['password']);

            return new Identity($user->userID, $user->role, $arr);
    }
}