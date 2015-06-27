<?php

namespace App\Model\Entities;

use DateTime;

/**
 * @property-read int $messageID
 * @property \DateTime $sent
 * @property string $subject
 * @property string $message
 * @property-read bool $deleted
 * @property-read bool $isReceived m:temporary
 * @property-read bool $isRead m:temporary
 * @property User|null $author m:hasOne(author:user)
 */
class Message extends BaseEntity
{
    const SENT = 'sent';
    const RECEIVED = 'received';

    const UNREAD = 0;
    const READ = 1;

    public function getRecipientsNames()
    {
        $recipientsNames = [];
        foreach ($this->row->referencing('user_message', 'messageID') as $userMessage) {
            $user = $userMessage->referenced('user', 'recipient');
            $recipientsNames[] = isset($user->username) ? $user->username : null;
        }

        return $recipientsNames;
    }

    public function isReceived()
    {
        return $this->row->isReceived;
    }

    public function isRead()
    {
        return $this->row->isRead;
    }

    public function isSystemMessage()
    {
        if (isset($this->row->author) and $this->author->role == 'system') {
            return true;
        }

        return false;
    }

    public function getMessageType()
    {
        return $this->isReceived() ? self::RECEIVED : self::SENT;
    }

}