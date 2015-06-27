<?php

namespace App\Model\Facades;

use App\Model\Notifications\SharedListingNotification;
use App\Model\Repositories\UserMessageRepository;
use App\Model\Repositories\MessageRepository;
use Exceptions\Logic\InvalidArgumentException;
use App\Model\Entities\Listing;
use Nette\Application\UI\Control;
use Nette\Security\User;
use Nette\Object;

class SystemMessagesFacade extends Object
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
     * @var User
     */
    private $user;


    public function __construct(
        UserMessageRepository $userMessageRepository,
        MessageRepository $messageRepository,
        User $user
    ) {
        $this->userMessageRepository = $userMessageRepository;
        $this->messageRepository = $messageRepository;
        $this->user = $user;
    }



}