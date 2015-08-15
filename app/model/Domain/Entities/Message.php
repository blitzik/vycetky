<?php

namespace App\Model\Entities;

use Exceptions\Logic\InvalidArgumentException;
use Nette\Utils\Validators;
use DateTime;

/**
 * @property-read int $messageID
 * @property-read \DateTime $sent
 * @property-read string $subject
 * @property-read string $message
 * @property-read bool $deleted
 * @property-read bool $isReceived m:temporary
 * @property-read bool $isRead m:temporary
 * @property-read User|null $author m:hasOne(author:user)
 */
class Message extends BaseEntity
{
    const SENT = 'sent';
    const RECEIVED = 'received';

    const UNREAD = 0;
    const READ = 1;

    /**
     * Allows initialize properties' default values
     */
    protected function initDefaults()
    {
        parent::initDefaults();

        $this->row->deleted = 0;
    }

    /**
     * @param string $subject
     * @param string $message
     * @param string $author
     * @param \DateTime $sent If $sent is null, sent is set to current date
     * @return Message
     */
    public static function loadState(
        $subject,
        $message,
        $author,
        DateTime $sent = null
    ) {
        $msg = new self;
        $msg->setSubject($subject);
        $msg->setMessage($message);
        $msg->setAuthor($author);

        if (is_null($sent)) {
            $sent = new DateTime();
        }

        $msg->setSent($sent);

        return $msg;
    }

    /**
     * @param string $subject
     */
    private function setSubject($subject)
    {
        $subject = trim($subject);
        Validators::assert($subject, 'string:1..80');
        $this->row->subject = $subject;
    }

    /**
     * @param string $message
     */
    private function setMessage($message)
    {
        $message = trim($message);
        Validators::assert($message, 'string:1..3000');
        $this->row->message = $message;
    }

    /**
     * @param string $author
     */
    private function setAuthor($author)
    {
        if ($author instanceof User and !$author->isDetached()) {
            $this->assignEntityToProperty($author, 'user');
        } else if (Validators::is($author, 'numericint')) {
            $this->row->author = $author;
            $this->row->cleanReferencedRowsCache('user', 'author');
        } else {
            throw new InvalidArgumentException(
                'Argument $author can by only instance of App\Entities\User or
                 integer number.'
            );
        }
    }

    /**
     * @param DateTime $sent
     */
    private function setSent(DateTime $sent)
    {
        $this->row->sent = $sent;
    }

    public function getRecipientsNames()
    {
        $recipientsNames = [];
        foreach ($this->row->referencing('user_message', 'messageID') as $userMessage) {
            $user = $userMessage->referenced('user', 'recipient');
            $recipientsNames[] = isset($user->username) ? $user->username : null;
        }

        return $recipientsNames;
    }

    /**
     * @return bool
     */
    public function isReceived()
    {
        return $this->row->isReceived == 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isRead()
    {
        return $this->row->isRead == 1 ? true : false;
    }

    /**
     * @return bool
     */
    public function isSystemMessage()
    {
        if (isset($this->row->author) and $this->author->role == 'system') {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getMessageType()
    {
        return $this->isReceived() ? self::RECEIVED : self::SENT;
    }

}