<?php

namespace App\FrontModule\Presenters;

use App\Model;
use Nette;

abstract class SecurityPresenter extends Nette\Application\UI\Presenter
{
    use \Nextras\Application\UI\SecuredLinksPresenterTrait;

    /**
     * @var \DateTime
     */
    protected $currentDate;

    protected function startup() {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {

            if ($this->getUser()->getLogoutReason() == Nette\Security\IUserStorage::INACTIVITY) {
                $this->flashMessage(
                    'Byl jste odhlášen z důvodu neaktivity. Přihlašte se prosím znovu.'
                );
            }
            $this->redirect(':User:Account:default');
        }

        $this->currentDate = new \DateTime();
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->template->username = $this->getUser()->getIdentity()->username;
        $this->template->currentDate = $this->currentDate;
    }

    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect(':User:Account:default');
    }
}