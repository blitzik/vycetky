<?php

use Tester\Assert;

$container = require './BaseFacadeTest.php';

class UserManagerTest extends BaseFacadeTest
{
    /**
     * @var \App\Model\Facades\UserManager
     */
    private $userManager;

    /**
     * @var \App\Model\Repositories\InvitationRepository
     */
    private $invitationRepository;

    public function __construct(\Nette\DI\Container $container)
    {
        parent::__construct($container);

        $this->userManager = $container->getService('userManager');
        $this->invitationRepository = $container->getService('invitationRepository');
    }

    /**
     * @param string $email
     * @param DateTime $validity
     * @return \App\Model\Entities\Invitation
     */
    private function generateInvitation(
        $email,
        DateTime $validity
    ) {
        $invitation = new \App\Model\Entities\Invitation(
            $email, $validity
        );
        $id = $this->connection
                   ->insert(
                       'invitation',
                       $invitation->getRowData()
                   )->execute();

        $this->makeEntityAlive($invitation, $id);

        return $invitation;
    }

    public function testInsertInvitation()
    {
        Assert::exception(
            function () {
                $this->userManager->createInvitation('test@test.test');
            },
            '\Exceptions\Runtime\UserAlreadyExistsException'
        );

        $invitation = $this->userManager->createInvitation('john@doe.aaa');

        $resultInv = $this->connection
                          ->query('SELECT invitationID, email, token, validity
                                   FROM invitation WHERE invitationID = ?',
                                   $invitation->invitationID)->fetch();

        Assert::same('john@doe.aaa', $resultInv['email']);
        Assert::same($invitation->token, $resultInv['token']);
        Assert::same($invitation->validity->format('Y-m-d H:i:s'),
                     $resultInv['validity']->format('Y-m-d H:i:s'));

        Assert::exception(
            function () {
                $this->userManager->createInvitation('john@doe.aaa');
            },
            '\Exceptions\Runtime\InvitationAlreadyExistsException'
        );
    }

    public function testCheckInvitation()
    {
        $invitation = $this->generateInvitation(
            'abc@def.gh', (new DateTime())->modify('+1 week')
        );

        Assert::exception(
            function () use ($invitation){
                // email is OK but token does not match
                $this->userManager
                     ->checkInvitation($invitation->email, 'abc');
            },
            '\Exceptions\Runtime\InvitationTokenMatchException'
        );

        Assert::exception(
            function () use ($invitation){
                // there is no Invitation with that email
                $this->userManager
                    ->checkInvitation('joh@doe.aa', 'abc');
            },
            '\Exceptions\Runtime\InvitationNotFoundException'
        );

        $this->connection // change validity to past
             ->update(
                 'invitation',
                 ['validity' => (new DateTime())->modify('-1 day')]
             )->where('email = ?', $invitation->email)->execute();

        Assert::exception(
            function () use ($invitation) {
                $this->userManager
                     ->checkInvitation($invitation->email, $invitation->token);
            },
            '\Exceptions\Runtime\InvitationExpiredException'
        );

        $count = $this->connection
                      ->query('SELECT COUNT(invitationID) AS count FROM invitation')
                      ->fetchSingle();

        Assert::same(0, $count);
    }

    public function testResetToken()
    {
        $token = \Nette\Utils\Random::generate(32);
        $tokenValidity = (new DateTime)->modify('+1 day');

        $this->connection
             ->update(
                 'user',
                 ['token' => $token,
                  'tokenValidity' => $tokenValidity]
             )->execute();

        $user = $this->userManager->getUserByID(self::TEST_USER_ID);

        $this->userManager->resetToken($user);

        $result = $this->connection
                       ->select('token, tokenValidity')
                       ->from('user')
                       ->where('userID = ?', self::TEST_USER_ID)
                       ->execute()->fetch();

        Assert::same(null, $result['token']);
        Assert::same(null, $result['tokenValidity']);
    }

    public function testResetPassword()
    {
        $this->userManager->resetPassword('test@test.test'); // test user email

        $result = $this->connection
                       ->select('token, tokenValidity')
                       ->from('user')
                       ->where('userID = ?', self::TEST_USER_ID)
                       ->execute()->fetch();

        Assert::notSame(null, $result['token']);
        Assert::notSame(null, $result['tokenValidity']);
    }

    public function testRegisterNewUser()
    {
        $invitation = $this->generateInvitation(
            'mail@mail.mail', (new DateTime())->modify('+1 week')
        );

        $user = new \App\Model\Entities\User(
            'John', 'asqw', 'john@doe.aa', '127.0.0.1'
        );

        Assert::exception(
            function () use ($user, $invitation) {
                $this->userManager->registerNewUser($user, $invitation);
            },
            '\Exceptions\Runtime\InvitationNotFoundException'
        );

        $originalToken = $invitation->token;
        $this->connection
             ->update(
                 'invitation',
                 ['email' => 'mail@mail.mail',
                  'token' => 'newTokenThatWontMatch']
             )->execute();
        $user->setEmail('mail@mail.mail');

        Assert::exception(
            function () use ($user, $invitation) {
                $this->userManager->registerNewUser($user, $invitation);
            },
            '\Exceptions\Runtime\InvitationTokenMatchException'
        );

        $val = (new DateTime())->modify('-1 month');
        $this->connection
             ->update(
                 'invitation',
                 ['token' => $originalToken,
                  'validity' => $val]
             )->execute();

        $invitation = $this->invitationRepository
                           ->getInvitation($invitation->email);

        Assert::exception(
            function () use ($user, $invitation) {
                $this->userManager->registerNewUser($user, $invitation);
            },
            '\Exceptions\Runtime\InvitationExpiredException'
        );

    }

}

$t = new UserManagerTest($container);
$t->run();