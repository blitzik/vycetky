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

        $this->messagesFacade
             ->sendMessage(
                 'subject text',
                 'message text',
                 1,
                 [$bob, $john->userID]
             );

        $numberOfMessages = $this->connection
                                 ->query(
                                     'SELECT COUNT(messageID) as count
                                      FROM message
                                      WHERE author = 1'
                                 )->fetchSingle();

        Assert::same(1, $numberOfMessages);


    }

}

$t = new MessagesFacadeTest($container);
$t->run();