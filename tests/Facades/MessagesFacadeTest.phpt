<?php

use Tester\Assert;

$container = require './BaseFacadeTest.php';

class MessagesFacadeTest extends BaseFacadeTest
{
    /**
     * @var \App\Model\Facades\MessagesFacade
     */
    private $messagesFacade;

    public function __construct(
        \Nette\DI\Container $container
    )
    {
        parent::__construct($container);

        $this->messagesFacade = $container->getService('messagesFacade');
    }

    public function testSendMessage()
    {
        $bob = $this->generateUser('Bob');
        $john = $this->generateUser('John');

        $message = $this->messagesFacade
                        ->sendMessage(
                            'subject text',
                            'message text',
                            1,
                            [$bob, $john->userID]
                        );

        $numberOfMessages = $this->connection
                                 ->query(
                                     'SELECT COUNT(messageID) as [count]
                                      FROM message
                                      WHERE author = 1'
                                 )->fetchSingle();

        Assert::same(1, $numberOfMessages);

        $umCount = $this->connection->query(
            'SELECT COUNT(userMessageID) as [count]
             FROM user_message
             WHERE messageID = ?', $message->messageID
        )->fetchSingle();

        Assert::same(2, $umCount);
    }

    public function testSendMessages()
    {
        $bob = $this->generateUser('Bob');
        $john = $this->generateUser('John');

        $msg1 = new \App\Model\Entities\Message(
            'Subject1', 'Message1', self::TEST_USER_ID
        );

        $msg2 = new \App\Model\Entities\Message(
            'Subject2', 'Message2', self::TEST_USER_ID
        );

        $msg3 = new \App\Model\Entities\Message(
            'Subject3', 'Message3', self::TEST_USER_ID
        );

        $messages = [$bob->userID => [$msg1, $msg2], $john->userID => $msg3];

        $resultMessages = $this->messagesFacade->sendMessages($messages);

        Assert::same(3, $this->connection
                             ->query('SELECT COUNT(messageID) FROM message
                                      WHERE author = ?', self::TEST_USER_ID)
                             ->fetchSingle()
        );

        foreach ($resultMessages as $userMessage) {
            Assert::false($userMessage->isDetached());
        }

        $numberOfMessages = $this->connection
             ->query(
                 'SELECT recipient, COUNT(userMessageID) as [count]
                  FROM user_message
                  GROUP BY recipient')
             ->fetchAssoc('recipient=count');

        Assert::same(2, $numberOfMessages[$bob->userID]);
        Assert::same(1, $numberOfMessages[$john->userID]);
    }

}

$t = new MessagesFacadeTest($container);
$t->run();