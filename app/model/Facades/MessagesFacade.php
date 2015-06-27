<?php

namespace App\Model\Facades;

use App\Model\Repositories\UserMessageRepository;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Repositories\MessageRepository;
use App\Model\Repositories\UserRepository;
use App\Model\Entities\UserMessage;
use App\Model\Entities\Message;
use Exceptions\Runtime\MessageLengthException;
use Nette\Security\User;
use Tracy\Debugger;
use Nette\Object;

class MessagesFacade extends Object
{

    /**
     * @var UserMessageRepository
     */
    private $userMessageRepository;

    /**
     * @var MessageRepository
     */
    private $messageRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;


    /**
     * @var \Transaction
     */
    private $transaction;

    /**
     * @var User
     */
    private $user;


    public function __construct(
        MessageRepository $messageRepository,
        UserMessageRepository $umr,
        \Transaction $transaction,
        MessageRepository $mr,
        UserRepository $ur,
        User $user
    ) {
        $this->messageRepository = $messageRepository;
        $this->userMessageRepository = $umr;
        $this->transaction = $transaction;
        $this->messageRepository = $mr;
        $this->userRepository = $ur;
        $this->user = $user;
    }


    /**
     * @param int $messageID
     * @param int $userID
     * @param string $type received or sent
     * @return Message
     * @throw MessageNotFoundException
     */
    public function getMessage($messageID, $userID, $type)
    {
        $message = $this->callMessageActionBasedOnType(
                $type,
                [$messageID, $userID],
                function ($messageID, $recipientID) {
                    return $this->messageRepository
                                ->getReceivedMessage($messageID, $recipientID);
                },
                function ($messageID, $authorID) {
                    return $this->messageRepository
                                ->getSentMessage($messageID, $authorID);
                }
            );

        if ($type === Message::RECEIVED and !$message->isRead()) {
            $this->userMessageRepository->markMessageAsRead($message, $userID);
        }

        return $message;
    }

    /**
     * @param string $messageType unread or read
     * @param $offset
     * @param $length
     * @return Message[]
     */
    public function findReceivedMessages($messageType, $offset, $length)
    {
        return $this->messageRepository
                    ->findReceivedMessages(
                        $this->user->id,
                        $messageType,
                        $offset,
                        $length
                    );
    }

    /**
     * @param string $messageType unread or read
     * @return int
     */
    public function getNumberOfReceivedMessages($messageType)
    {
        return $this->messageRepository
                    ->getNumberOfReceivedMessages($this->user->id, $messageType);
    }

    /**
     * @param $offset
     * @param $length
     * @return Message[]
     */
    public function findSentMessages($offset, $length)
    {
        return $this->messageRepository
                    ->findSentMessages($this->user->id, $offset, $length);
    }

    /**
     * @return int
     */
    public function getNumberOfSentMessages()
    {
        return $this->messageRepository->getNumberOfSentMessages($this->user->id);
    }

    /**
     * @param $subject
     * @param $text
     * @param $authorID
     * @param array $recipientsIDs
     * @throws MessageLengthException
     * @throws \DibiException
     */
    public function sendMessage($subject, $text, $authorID, array $recipientsIDs)
    {
        try {
            $this->transaction->begin();

                $message = new Message();
                $message->subject = $subject;
                $message->message = $text;
                $message->sent = new \DateTime();
                $message->author = $authorID;

                $this->messageRepository->persist($message);

                $userMessages = [];
                foreach ($recipientsIDs as $recipientID) {
                    $um = new UserMessage();
                    $um->message = $message;
                    $um->recipient = $recipientID;

                    $userMessages[] = $um;
                }

                $this->userMessageRepository->sendMessagesToRecipients($userMessages);

            $this->transaction->commit();

        } catch (\DibiException $e) {

            $this->transaction->rollback();

            if ($e->getCode() === 1406) { // too long data for database column
                throw new MessageLengthException;
            }

            Debugger::log($e, Debugger::ERROR);
            throw $e;
        }
    }

    /**
     * @param array $messages Key => recipientID, Value = Message entity
     * @throws InvalidArgumentException
     * @throws \DibiException
     */
    public function sendMessages(array $messages)
    {
        foreach ($messages as $recipientID => $message) {
            if (!is_int($recipientID) or !$message instanceof Message or
                !$message->isDetached()) {
                throw new InvalidArgumentException(
                    'Wrong structure of argument'
                );
            }
        }

        try {
            $this->transaction->begin();

            $this->messageRepository->saveMessages($messages);

            $recipientsMessages = [];
            foreach ($messages as $recipientID => $message) {
                $recipientMessage = new UserMessage();
                $recipientMessage->message = $message;
                $recipientMessage->recipient = $recipientID;

                $recipientsMessages[] = $recipientMessage;
            }

            $this->userMessageRepository->sendMessagesToRecipients($recipientsMessages);

            $this->transaction->commit();

        } catch (\DibiException $e) {
            $this->transaction->rollback();
            Debugger::log($e, Debugger::ERROR);

            throw $e;
        }
    }

    /**
     * @param int $messageID
     * @param string $type received or sent
     */
    public function removeMessage($messageID, $type)
    {
        $this->callMessageActionBasedOnType(
            $type,
            [$messageID, $this->user->id],
            function ($messageID, $recipientID) {
                $this->userMessageRepository
                     ->removeMessage($messageID, $recipientID);
            },

            function ($messageID, $authorID) {
                $this->messageRepository
                     ->removeAuthorMessage($messageID, $authorID);
            }
        );
    }

    public function removeMessages(array $messagesIDs, $type)
    {
        $this->callMessageActionBasedOnType(
            $type,
            [$messagesIDs, $this->user->id],
            function ($messagesIDs, $recipientID) {
                $this->userMessageRepository
                     ->removeMessages($messagesIDs, $recipientID);
            },
            function ($messagesIDs, $authorID) {
                $this->messageRepository
                     ->removeAuthorMessages($messagesIDs, $authorID);
            }
        );
    }

    /**
     * @param $type
     * @param array $args
     * @param callable $callbackForReceived
     * @param callable $callbackForSent
     * @return mixed
     */
    private function callMessageActionBasedOnType(
        $type,
        array $args,
        Callable $callbackForReceived,
        Callable $callbackForSent
    )
    {
        switch ($type) {
            case Message::RECEIVED:
                return call_user_func_array($callbackForReceived, $args);
                break;

            case Message::SENT:
                return call_user_func_array($callbackForSent, $args);
                break;

            default:
                throw new InvalidArgumentException('Argument $type has wrong value.');
        }
    }
}