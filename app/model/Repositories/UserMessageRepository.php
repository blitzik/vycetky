<?php

namespace App\Model\Repositories;

use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\UserMessage;
use App\Model\Entities\Message;
use Tracy\Debugger;

class UserMessageRepository extends BaseRepository
{
    public function markMessageAsRead(Message $message, $recipientID)
    {
        if ($message->isDetached()) {
            throw new InvalidArgumentException(
                'Argument $message must be attached entity.'
            );
        }

        $this->connection->query(
            'UPDATE %n', $this->getTable(), 'SET [read] = 1
             WHERE recipient = ?', $recipientID,
            'AND messageID = ?', $message->messageID
        );
    }

    /**
     * @param array $messages
     * @throws InvalidArgumentException
     */
    public function sendMessagesToRecipients(array $messages)
    {
        $values = [];
        foreach ($messages as $message) {
            if (!$message instanceof UserMessage or
                !$message->isDetached()) {
                throw new InvalidArgumentException(
                    'Invalid set of ListingItems given.'
                );
            }
            $values[] = $message->getModifiedRowData();
        }

        $this->connection->query('INSERT INTO %n %ex', $this->getTable(), $values);
    }

    /**
     * @param $messageID
     * @param $recipientID
     */
    public function removeMessage($messageID, $recipientID)
    {
        $this->connection
             ->query('UPDATE %n', $this->getTable(),
                     'SET deleted = 1
                      WHERE recipient = ?', $recipientID,'
                      AND messageID = ?', $messageID);
    }

    /**
     * @param array $IDs
     * @throws \DibiException
     */
    public function removeMessages(array $IDs, $recipientID)
    {
        $this->connection->query('UPDATE %n', $this->getTable(),
                                 'SET deleted = 1
                                  WHERE messageID IN (?)', $IDs,
                                 'AND recipient = ?', $recipientID);
    }

}