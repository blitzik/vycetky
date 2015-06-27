<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\ListingTable\IListingTableControlFactory;
use App\Model\Components\IListingActionsMenuControlFactory;
use App\Model\Components\IListingsOverviewControlFactory;
use App\Model\Components\IMassItemsChangeControlFactory;
use App\Model\Components\ISharingListingControlFactory;
use App\Model\Components\IListingFormControlFactory;
use App\Model\Components\IFilterControlFactory;
use App\Model\Components\ListingFormFactory;
use App\Model\Facades\MessagesFacade;
use App\Model\Facades\ItemFacade;
use App\Model\Facades\UserManager;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;
use Exceptions\Runtime;
use App\Model\Entities;
use Nette\Forms\Controls\SubmitButton;

class ListingPresenter extends SecurityPresenter
{
    use TListing;

    /** @persistent */
    public $backlink = null;

    /**
     * @var array
     */
    private $companyParameters;

    /**
     * @var ISharingListingControlFactory
     * @inject
     */
    public $sharingListingTableControlFactory;

    /**
     * @var IListingActionsMenuControlFactory
     * @inject
     */
    public $listingActionsMenuControlFactory;

    /**
     * @var IMassItemsChangeControlFactory
     * @inject
     */
    public $massItemChangeControlFactory;

    /**
     * @var IListingTableControlFactory
     * @inject
     */
    public $listingTableControlFactory;

    /**
     * @var IListingFormControlFactory
     * @inject
     */
    public $listingFormControlFactory;

    /**
     * @var IListingsOverviewControlFactory
     * @inject
     */
    public $listingsOverviewFactory;

    /**
     * @var IFilterControlFactory
     * @inject
     */
    public $filterControlFactory;

    /**
     * @var ListingFormFactory
     * @inject
     */
    public $listingFormFactory;

    /**
     * @var MessagesFacade
     * @inject
     */
    public $messageFacade;

    /**
     * @var UserManager
     * @inject
     */
    public $userManager;

    /**
     * @var ItemFacade
     * @inject
     */
    public $itemFacade;

    /**
     *
     * @var Entities\Listing
     */
    private $listing;

    private $numberOfMessages;


    protected function createComponentListingActionsMenu()
    {
        $comp = $this->listingActionsMenuControlFactory->create($this->listing);
        return $comp;
    }

    public function setCompanyParameters(array $companyParameters)
    {
        $this->companyParameters = $companyParameters;
    }

    /*
     * --------------------
     * ----- OVERVIEW -----
     * --------------------
     */

    public function actionOverview($month, $year)
    {
        $this->setPeriodParametersForFilter($year, $month);
    }

    public function renderOverview($month, $year)
    {
        $this->numberOfMessages = $this->messageFacade
             ->getNumberOfReceivedMessages(Entities\Message::UNREAD);

        $this->template->numberOfMessages = $this->numberOfMessages;
    }

    protected function createComponentListingsOverview()
    {
        $comp = $this->listingsOverviewFactory
                     ->create(
                         ['year'  => $this->presenter->getParameter('year'),
                          'month' => $this->presenter->getParameter('month')]
                     );

        $comp->setHeading('Mé výčetky');

        return $comp;
    }

    protected function createComponentFilter()
    {
        return $this->filterControlFactory->create();
    }

    /*
     * ------------------------------
     * ----- ADD / EDIT Listing -----
     * ------------------------------
     */

    public function actionAdd()
    {
    }

    public function renderAdd()
    {
    }

    public function actionEdit($id)
    {
        $this->listing = $this->getListingByID($id);

    }

    public function renderEdit($id)
    {
    }

    protected function createComponentListingForm()
    {
        return $this->listingFormControlFactory->create($this->listing);
    }

    /*
     * -----------------------------------
     * ----- REMOVE existing Listing -----
     * -----------------------------------
     */

    public function actionRemove($id)
    {
        $this->listing = $this->getEntireListingByID($id);
    }

    public function renderRemove($id)
    {
        $this->template->listing = $this->listing;
    }

    protected function createComponentDeleteListingForm()
    {
        $form = new Form();

        $form->addSubmit('delete', 'Odstranit výčetku')
                ->onClick[] = callback($this, 'processDeleteListing');

        $form->addSubmit('cancel', 'Vrátit se zpět')
                ->setValidationScope(null)
                ->onClick[] = callback($this, 'processCancel');

        $form->addProtection();

        return $form;
    }

    public function processDeleteListing(\Nette\Forms\Controls\SubmitButton $button)
    {
        $this->listingFacade->removeListing($this->listing);

        $this->flashMessage('Výčetka byla odstraněna.', 'success');

        $this->backlink = NULL;
        $this->redirect(
            'Listing:overview',
            array('month' => $this->listing->month,
                  'year'  => $this->listing->year)
        );
    }

    public function processCancel()
    {
        if (isset($this->backlink))
            $this->restoreRequest($this->backlink);

        $this->redirect(
            'Listing:overview',
            array('month' => $this->listing->month,
                  'year'  => $this->listing->year)
        );
    }

    /*
     * ------------------
     * ----- DETAIL -----
     * ------------------
     */

    public function actionDetail($id)
    {
        try {
            $this->listing = $this->getEntireListingByID($id);

        } catch (Runtime\ListingNotFoundException $li) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'warning');
            $this->redirect('Listing:overview');
        }
    }

    public function renderDetail($id)
    {
        $this->template->listing = $this->listing;
    }

    protected function createComponentListingItemsTable()
    {
        $comp = $this->listingTableControlFactory->create($this->listing);

        return $comp;
    }

    /*
     * ------------------------
     * ----- Copy Listing -----
     * ------------------------
     */

    public function actionCopy($id)
    {
        $this->listing = $this->getListingByID($id);

        $this['simpleCopyForm']['month']->setDefaultValue(
            TimeUtils::getMonthName($this->listing->month)
        );
        $this['simpleCopyForm']['year']->setDefaultValue($this->listing->year);
        $this['simpleCopyForm']['description']->setDefaultValue(
            $this->listing->description == null ?
                                           'Popis nebyl zadán' :
                                           $this->listing->description);
    }

    public function renderCopy($id)
    {
    }

    protected function createComponentSimpleCopyForm()
    {
        $form = new Form();

        $form->addText('month', 'Měsíc', 4)
                ->setDisabled();

        $form->addText('year', 'Rok', 4)
                ->setDisabled();

        $form->addText('description', 'Popis výčetky')
                ->setDisabled()
                ->getControlPrototype()->class = 'description';

        $form->addSubmit('save', 'Vytvořit kopii výčetky');

        $form->onSuccess[] = [$this, 'processSimpleCopy'];

        return $form;
    }

    public function processSimpleCopy(Form $form)
    {
        try {
                $this->listingFacade->establishListingCopy($this->listing);

        } catch (\DibiException $e) {
            $this->flashMessage(
                'Kopie výčetky nemohla být založena. Zkuste prosím akci
                 opakovat později.',
                'error'
            );
            $this->redirect('this');
        }

        $this->flashMessage('Byla založena kopie výčetky.', 'success');
        $this->redirect(
            'Listing:overview',
            array('year'  => $this->listing->year,
                  'month' => $this->listing->month)
        );
    }

    /*
     * ----------------------------
     * ----- Mass item change -----
     * ----------------------------
     */

    public function actionMassItemsChange($id)
    {
        $this->listing = $this->getEntireListingByID($id);
        if ($this->listing->workedDays == 0) {
            $this->flashMessage('Nelze upravovat prázdnou výčetku.', 'warning');
            $this->redirect('Listing:detail', ['id' => $id]);
        }
    }

    public function renderMassItemsChange($id)
    {

    }

    protected function createComponentMassItemChangeTable()
    {
        $comp = $this->massItemChangeControlFactory->create($this->listing);

        return $comp;
    }

    /*
     * ----------------------------
     * ----- Sharing listings -----
     * ----------------------------
     */

    public function actionShare($id)
    {
        $this->listing = $this->getEntireListingByID($id);
        if ($this->listing->workedDays == 0) {
            $this->flashMessage('Nelze sdílet prázdnou výčetku.', 'warning');
            $this->redirect('Listing:detail', ['id' => $id]);
        }
    }

    public function renderShare($id)
    {

    }

    protected function createComponentListingTableForSharing()
    {
        return $this->sharingListingTableControlFactory->create($this->listing);
    }

    /*
     * --------------------------
     * ----- Generating PDF -----
     * --------------------------
     */

    public function actionPdfGeneration($id)
    {
        $this->listing = $this->getEntireListingByID($id);
        $user = $this->userManager->getUserByID($this->user->id);

        $this['listingResultSettings']['name']->setDefaultValue($user->name);
    }

    public function renderPdfGeneration($id)
    {
        $this->template->listing = $this->listing;
        $this->template->_form = $this['listingResultSettings'];
    }

    protected function createComponentListingResultSettings()
    {
        $form = new Form();

        $form->addText('employer', 'Zaměstnavatel:', 25, 70)
                ->setDefaultValue($this->companyParameters['name']);

        $form->addText('name', 'Jméno:', 25, 70);

        $form->addCheckbox('wage', 'Zobrazit "Základní mzdu"')
                ->setDefaultValue(true);

        $form->addCheckbox('otherHours', 'Zobrazit "Ostatní hodiny"');
        $form->addCheckbox('workedHours', 'Zobrazit "Odpracované hodiny"');
        $form->addCheckbox('lunch', 'Zobrazit hodiny strávené obědem');

        $form->addSubmit('generatePdf', 'Vygeneruj PDF')
                ->onClick[] = [$this, 'generatePdf'];

        $form->addSubmit('reset', 'Reset nastavení')
                ->onClick[] = [$this, 'processReset'];

        return $form;
    }

    public function generatePdf(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();

        $template = $this->createTemplate()
                         ->setFile(__DIR__ . '/../templates/Listing/pdf.latte');

        $template->itemsColletion = $this->itemFacade
                                         ->generateEntireTable(
                                             $this->listing->listingItems,
                                             $this->listing->period
                                         );

        $template->listing = $this->listing;
        $template->username = $values['name'] == null ?: $values['name'];
        $template->employer = $values['employer'];
        $template->employeeName = $values['name'];

        $template->wage = $values['wage'];
        $template->otherHours = $values['otherHours'];
        $template->workedHours = $values['workedHours'];
        $template->lunchHours = $values['lunch'];

        $pdf = new \PdfResponse\PdfResponse($template);

        $this->presenter->sendResponse($pdf);
    }

    public function processReset(SubmitButton $button)
    {
        $this->redirect('this');
    }

}