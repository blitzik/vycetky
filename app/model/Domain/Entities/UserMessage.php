<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;

/**
 * @property-read int $userMessageID
 * @property-read Message $message m:hasOne(messageID:message)
 * @property-read User|null $recipient m:hasOne(recipient:user)
 * @property-read int $read
 */
class UserMessage extends BaseEntity
{

    protected function initDefaults()
    {
        parent::initDefaults();

        $this->row->read  = Message::UNREAD;
    }

    /**
     * @param Message $message
     * @param $recipient
     * @return UserMessage
     */
    public static function loadState(
        Message $message,
        $recipient
    ) {
        $um = new self;
        $um->setMessage($message);
        $um->setRecipient($recipient);

        return $um;
    }

    /**
     * @param $recipient
     */
    private function setRecipient($recipient)
    {
        if ($recipient instanceof User and !$recipient->isDetached()) {
            $this->assignEntityToProperty($recipient, 'user');
        } else if (Validators::is($recipient, 'numericint')) {
            $this->row->recipient = $recipient;
        } else {
            throw new InvalidArgumentException(
                'Argument $recipient can by only instance of App\Entities\User or
                 integer number.'
            );
        }
    }

    /**
     * @param Message $message
     */
    private function setMessage(Message $message)
    {
        $message->checkEntityState();
        $this->assignEntityToProperty($message, 'message');
    }

    public function markAsRead()
    {
        $this->row->read = Message::READ;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        return $this->row->read == Message::READ ? true : false;
    }

}