<?php

namespace App\Model\Facades;

use App\Model\Entities\Invitation;
use App\Model\Entities\User;
use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use App\Model\Repositories;
use \Exceptions\Runtime;
use Tracy\Debugger;
use Nette\Object;

class UserManager extends Object
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
     * @param string $email
     * @param string $token
     * @return Invitation
     * @throws Runtime\InvitationNotFoundException
     * @throws Runtime\InvitationExpiredException
     * @throws Runtime\InvitationTokenMatchException
     */
    public function checkInvitation($email, $token)
    {
        Validators::assert($email, 'email');

        $invitation = $this->invitationRepository
                           ->getInvitation($email);

        if (!$this->isInvitationTimeValid($invitation)) {
            $this->removeInvitation($invitation);
            throw new Runtime\InvitationExpiredException;
        }

        if ($token !== $invitation->token) {
            throw new Runtime\InvitationTokenMatchException;
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
     * @return void
     */
    public function resetToken(User $user)
    {
        $user->resetToken();
        $this->userRepository->persist($user);
    }

    /**
     * @param User $user
     * @return int User ID
     */
    public function saveUser(User $user)
    {
        return $this->userRepository->persist($user);
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
     * @return User
     * @throws Runtime\UserNotFoundException
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

        $user->createToken();

        $this->userRepository->persist($user);

        return $user;
    }

    /**
     * @param string $email
     * @return Invitation
     * @throws Runtime\InvitationAlreadyExistsException
     * @throws Runtime\UserAlreadyExistsException
     */
    public function createInvitation($email)
    {
        Validators::assert($email, 'email');

        try {
            $this->findUserByEmail($email);

            throw new Runtime\UserAlreadyExistsException;

        } catch (Runtime\UserNotFoundException $e) {
            $invitation = new Invitation(
                $email,
                (new \DateTime)->modify('+1 week')
            );

            try {
                $this->invitationRepository->insertInvitation($invitation);

            } catch (Runtime\InvitationAlreadyExistsException $ie) {
                $existingInvitation = $this->invitationRepository->getInvitation($email);
                if ($this->isInvitationTimeValid($existingInvitation)) {
                    throw $ie;
                } else {
                    $this->removeInvitation($existingInvitation);
                    $this->invitationRepository->insertInvitation($invitation);
                }
            }

            return $invitation;
        }
    }

    /**
     * @param User $user
     * @param Invitation $invitation
     * @return void
     * @throws Runtime\DuplicateUsernameException
     * @throws Runtime\DuplicateEmailException
     * @throws Runtime\InvitationNotFoundException
     * @throws Runtime\InvitationExpiredException
     * @throws Runtime\InvitationTokenMatchException
     * @throws \DibiException
     */
    public function registerNewUser(
        User $user,
        Invitation $invitation
    ) {
        if (!$user->isDetached()) {
            throw new InvalidArgumentException(
                'Only detached instances of Entity ' . User::class . ' can pass.'
            );
        }

        $this->checkInvitation($user->email, $invitation->token);

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
     * @param Invitation $invitation
     * @return boolean TRUE - valid; FALSE - invalid
     */
    private function isInvitationTimeValid(Invitation $invitation)
    {
        $currentDate = new \DateTime;
        if ($currentDate > $invitation->validity) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param array|null $withoutUsers
     * @return array
     */
    public function findAllUsers(array $withoutUsers = null)
    {
        $users = $this->userRepository->findAllUsers($withoutUsers);

        return $users;
    }
}