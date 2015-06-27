<?php

namespace App\FrontModule\Presenters;

use InvoiceTime;
use App\Model\Entities\Listing;
use App\Model\Entities\WorkedHours;
use App\Model\Notifications\SharedListingNotification;
use App\Model\Notifications\EmailNotifier;
use App\Model\Repositories\ListingItemRepository;
use App\Model\Repositories\WorkedHoursRepository;
use App\Model\Repositories\LocalityRepository;
use App\Model\Repositories\ListingRepository;
use App\Model\Facades\MessagesFacade;
use App\Model\Services\ItemService;
use App\Model\Facades\ItemFacade;
use Nette\Application\UI\Form;

class TestPresenter extends SecurityPresenter
{

    /**
     * @var ListingRepository
     * @inject
     */
    public $listRep;

    /**
     * @var ListingItemRepository
     * @inject
     */
    public $itemRep;

    /**
     * @var LocalityRepository
     * @inject
     */
    public $localityRep;

    /**
     * @var ItemService
     * @inject
     */
    public $itemService;

    /**
     * @var WorkedHoursRepository
     * @inject
     */
    public $whRepo;

    /**
     * @var ItemFacade
     * @inject
     */
    public $itemFacade;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messRep;

    public $onSend;

    public function actionDefault()
    {
        $x = 0;
        if (isset($x)) {
            dump('YES');
        }
    }

    public function renderDefault()
    {

    }

    protected function createComponentForm()
    {
        $form = new Form();

        $form->addSubmit('send', 'Send');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, $values)
    {
        $this->onSend();
    }
}
