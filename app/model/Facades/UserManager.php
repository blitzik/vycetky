<?php

namespace App\Model\Facades;

use App\Model\Entities\Invitation;
use Nette\Security\Passwords;
use App\Model\Entities\User;
use App\Model\Repositories;
use \Exceptions\Runtime;
use Tracy\Debugger;
use Nette;

class UserManager extends Nette\Object
{
    /**
     *
     * @var Repositories\WorkedHoursRepository
     */
    private $workingHourRepository;

    /**
     *
     * @var Repositories\InvitationRepository
     */
    private $invitationRepository;

    /**
     *
     * @var Repositories\UserRepository
     */
    private $userRepository;

    /**
     *
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var \Nette\Http\IRequest
     */
    private $httpRequest;

    public function __construct(
        Repositories\WorkedHoursRepository $workingHourRepository,
        Repositories\InvitationRepository $invitationRepository,
        Repositories\UserRepository $userRepository,
        \Nette\Http\IRequest $httpRequest,
        \Transaction $transaction
    ) {
        $this->workingHourRepository = $workingHourRepository;
        $this->invitationRepository = $invitationRepository;
        $this->userRepository = $userRepository;
        $this->transaction = $transaction;
        $this->httpRequest = $httpRequest;
    }

    /**
     *
     * @param string $token
     * @return Invitation
     * @throws Runtime\TokenNotFoundException
     * @throws Runtime\TokenValidityExpiredException
     */
    public function checkToken($token)
    {
        $invitation = $this->invitationRepository->checkToken($token);
        if (!$this->isInvitationTimeValid($invitation->validity)) {
            $this->removeInvitation($invitation);
            throw new Runtime\TokenValidityExpiredException;
        }

        return $invitation;
    }

    /**
     *
     * @param Invitation $invitation
     * @return void
     */
    public function removeInvitation(Invitation $invitation)
    {
        $this->invitationRepository->delete($invitation);
    }

    /**
     *
     * @param User $user
     * @param string $password
     * @return void
     * @throws \DibiException
     */
    public function changePassword(User $user, $password)
    {
        $user->password = Passwords::hash($password);

        try {
            $this->transaction->begin();

                $this->resetToken($user);
                $this->userRepository->persist($user);

            $this->transaction->commit();

        } catch (\DibiException $e) {

            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     *
     * @param User $user
     * @return void
     */
    public function resetToken(User $user)
    {
        $user->token = null;
        $user->tokenValidity = null;

        $this->userRepository->persist($user);
    }

    /**
     * @param User $user
     */
    public function saveUser(User $user)
    {
        $this->userRepository->persist($user);
    }

    /**
     *
     * @param string $email
     * @return User
     * @throws Runtime\UserNotFoundException
     */
    public function findUserByEmail($email)
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * @param $userID
     * @return mixed
     */
    public function getUserByID($userID)
    {
        return $this->userRepository->getUserByID($userID);
    }

    /**
     *
     * @param string $email
     * @return User
     * @throws Runtime\UserNotFoundException
     */
    public function resetPassword($email)
    {
        $user = $this->findUserByEmail($email);

        $token = \Nette\Utils\Random::generate(32);

        $currentDay = new \DateTime();
        $tokenValidity = $currentDay->modify('+1 day');

        $user->token = $token;
        $user->tokenValidity = $tokenValidity;

        $this->userRepository->persist($user);

        return $user;
    }

    /**
     *
     * @param string $email
     * @return Invitation
     * @throws Runtime\InvitationAlreadyExistsException
     */
    public function insertInvitation($email)
    {
        $invitation = $this->invitationRepository->getInvitation($email);
        if ($invitation instanceof Invitation) {

            if (!$this->isInvitationTimeValid($invitation->validity)) {
                 $this->removeInvitation($invitation);
            } else {
                 throw new Runtime\InvitationAlreadyExistsException;
            }
        }

        $regHash = \Nette\Utils\Random::generate(32);
        $currentDate = new \DateTime;

        $invitation = new Invitation;
        $invitation->email = $email;
        $invitation->regHash = $regHash;
        $invitation->validity = $currentDate->modify('+1 week');

        $this->invitationRepository->persist($invitation);

        return $invitation;
    }

   /**
     *
     * @param User $user
     * @param Invitation $invitation
     * @return void
     * @throws Runtime\InvalidUserInvitationEmailException
     * @throws Runtime\DuplicateUsernameException
     * @throws Runtime\DuplicateEmailException
     * @throws \DibiException
     */
    public function registerNewUser(
        User $user,
        Invitation $invitation
    ) {
        if ($user->email != $invitation->email)
            throw new Runtime\InvalidUserInvitationEmailException;

        $user->password = Passwords::hash($user->password);

        try {
            $this->transaction->begin();

                $this->userRepository->persist($user);
                $this->removeInvitation($invitation);

            $this->transaction->commit();

        } catch (\DibiException $e) {

            if ($e->getCode() == 1062) {

                try {
                    $this->userRepository->checkUsername($user->username);

                } catch (Runtime\UserAlreadyExistsException $usernameException) {
                    $this->transaction->rollback();
                    throw new Runtime\DuplicateUsernameException;
                }

                try {
                    $this->userRepository->checkEmail($user->email);

                } catch (Runtime\UserAlreadyExistsException $emailException) {
                    $this->transaction->rollback();
                    throw new Runtime\DuplicateEmailException;
                }

            }

            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    public function getTotalWorkedStatistics($userID)
    {
        return $this->workingHourRepository->getTotalWorkedStatistics($userID);
    }

    /**
     * Compares given time with current time
     *
     * @param \DateTime $datetime
     * @return boolean TRUE - valid; FALSE - invalid
     */
    private function isInvitationTimeValid(\DateTime $datetime)
    {
        $currentDate = new \DateTime;
        if ($currentDate > $datetime) {
            return FALSE;
        }

        return TRUE;
    }

    public function findAllUsers(array $withoutUsers = null)
    {
        $users = $this->userRepository->findAllUsers($withoutUsers);

        return $users;
    }
}