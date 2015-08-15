<?php

namespace App\Model\Facades;

use App\Model\Repositories\UserMessageRepository;
use Exceptions\Logic\InvalidArgumentException;
use Exceptions\Runtime\MessageLengthException;
use App\Model\Repositories\MessageRepository;
use App\Model\Repositories\UserRepository;
use App\Model\Entities\UserMessage;
use App\Model\Entities\Message;
use Nette\Utils\Validators;
use Nette\Security\User;
use Tracy\Debugger;

class MessagesFacade extends BaseFacade
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

    public function __construct(
        MessageRepository $messageRepository,
        UserMessageRepository $umr,
        \Transaction $transaction,
        MessageRepository $mr,
        UserRepository $ur,
        User $user
    ) {
        parent::__construct($user);

        $this->messageRepository = $messageRepository;
        $this->userMessageRepository = $umr;
        $this->transaction = $transaction;
        $this->messageRepository = $mr;
        $this->userRepository = $ur;
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
     * @param int $offset
     * @param int $length
     * @param User|int|null $user
     * @return Message[]
     */
    public function findReceivedMessages(
        $messageType,
        $offset,
        $length,
        $user = null
    ) {
        Validators::assert($offset, 'numericint');
        Validators::assert($length, 'numericint');
        $userID = $this->getUserID($user);;

        return $this->messageRepository
                    ->findReceivedMessages(
                        $userID,
                        $messageType,
                        $offset,
                        $length
                    );
    }

    /**
     * @param string $messageType unread or read
     * @param User|int|null $user
     * @return int
     */
    public function getNumberOfReceivedMessages($messageType, $user = null)
    {
        $userID = $this->getUserID($user);

        return $this->messageRepository
                    ->getNumberOfReceivedMessages($userID, $messageType);
    }

    /**
     * @param int $offset
     * @param int $length
     * @param User|int|null $user
     * @return Message[]
     */
    public function findSentMessages($offset, $length, $user = null)
    {
        $userID = $this->getUserID($user);

        return $this->messageRepository
                    ->findSentMessages($userID, $offset, $length);
    }

    /**
     * @param User|int|null $user
     * @return int
     */
    public function getNumberOfSentMessages($user = null)
    {
        $userID = $this->getUserID($user);

        return $this->messageRepository->getNumberOfSentMessages($userID);
    }

    /**
     * @param string $subject
     * @param string $text
     * @param \App\Model\Entities\User|int $author
     * @param array $recipients IDs or App\Entities\User instances
     * @throws MessageLengthException
     * @throws \DibiException
     */
    public function sendMessage($subject, $text, $author, array $recipients)
    {
        try {
            $this->transaction->begin();

                $message = Message::loadState($subject, $text, $author);

                $this->messageRepository->persist($message);

                $userMessages = [];
                foreach ($recipients as $recipient) {
                    $um = UserMessage::loadState($message, $recipient);

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
                $recipientMessage = UserMessage::loadState($message, $recipientID);

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
     * @param User|int|null $user
     * @param string $type received or sent
     */
    public function removeMessage($messageID, $type, $user = null)
    {
        $userID = $this->getUserID($user);

        $this->callMessageActionBasedOnType(
            $type,
            [$messageID, $userID],
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

    /**
     * @param array $messagesIDs
     * @param string$type
     * @param User|int|null $user
     */
    public function removeMessages(array $messagesIDs, $type, $user = null)
    {
        $userID = $this->getUserID($user);

        $this->callMessageActionBasedOnType(
            $type,
            [$messagesIDs, $userID],
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