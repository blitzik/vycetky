<?php

namespace App\Model\Facades;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\User;
use Nette\Utils\Validators;
use Nette\Object;

abstract class BaseFacade extends Object
{
    /**
     * @var \Nette\Security\User
     */
    protected $user;

    public function __construct(\Nette\Security\User $user)
    {
        $this->user = $user;
    }

    /**
     * If argument $user is null, method returns ID of currently signed in user
     *
     * @param \App\Model\Entities\User|int|null $user
     * @return int
     */
    protected function getUserID($user)
    {
        $id = null;
        if ($user instanceof User and !$user->isDetached()) {
            $id = $user->userID;
        } else if (Validators::is($user, 'numericint')) {
            $id = $user;
        } else if (is_null($user)) {
            $id = $this->user->id;
        } else {
            throw new InvalidArgumentException(
                'Argument $user must be instance of \App\Model\Entities\User
                 or integer number.'
            );
        }

        return $id;
    }
}