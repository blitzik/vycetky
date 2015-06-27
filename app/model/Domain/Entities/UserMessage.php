<?php

namespace App\Model\Entities;

/**
 * @property-read int $userMessageID
 * @property Message $message m:hasOne(messageID:message)
 * @property User|null $recipient m:hasOne(recipient:user)
 * @property-read int $read
 */
class UserMessage extends BaseEntity
{

    protected function initDefaults()
    {
        parent::initDefaults();

        $this->row->read  = Message::UNREAD;
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