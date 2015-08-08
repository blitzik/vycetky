<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\ItemUpdateFormFactory;
use Nette\Application\Responses\JsonResponse;
use App\Model\Facades\LocalityFacade;
use App\Model\Facades\ItemFacade;
use Nette\Application\UI\Form;
use App\Model\Time\TimeUtils;
use \App\Model\Entities;
use \Exceptions\Runtime;

class ItemPresenter extends SecurityPresenter
{
    use TListing;

    /**
     * @var ItemUpdateFormFactory
     * @inject
     */
    public $itemUpdateFormFactory;

    /**
     * @var LocalityFacade
     * @inject
     */
    public $localityFacade;

    /**
     * @var ItemFacade
     * @inject
     */
    public $itemFacade;

    /**
     * @var Entities\ListingItem
     */
    private $listingItem;

    /**
     *
     * @var Entities\Listing
     */
    private $listing;


    /**
     * @var \DateTime
     */
    private $date;

    /*
     * ------------------
     * ----- UPDATE -----
     * ------------------
     */

    public function actionEdit($id, $day)
    {
        try {
            $this->listing = $this->listingFacade->getListingByID($id);
            $this->date = TimeUtils::getDateTimeFromParameters(
                $this->listing->year,
                $this->listing->month,
                $day
            );
            if ($this->date === false)
                $this->redirect('Listing:detail', ['id' => $this->listing->listingID]);

            $this->listingItem = $this->itemFacade->getListingItemByDay($day, $this->listing);

        } catch (Runtime\ListingNotFoundException $l) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'error');
            $this->redirect(
                'Listing:overview',
                ['year'  => $this->currentDate->format('Y'),
                 'month' => $this->currentDate->format('n')]
            );

        } catch (Runtime\ListingItemNotFoundException $li) {

            $this->listingItem = new Entities\ListingItem();
        }

        if (!$this->listingItem->isDetached()) {

            $formData['lunch'] = $this->listingItem
                                      ->workedHours
                                      ->lunch->toTimeWithComma();

            $formData['workEnd'] = $this->listingItem
                                        ->workedHours
                                        ->workEnd->toHoursAndMinutes(true);

            $formData['workStart'] = $this->listingItem
                                          ->workedHours
                                          ->workStart->toHoursAndMinutes(true);

            $formData['otherHours'] = $this->listingItem
                                           ->workedHours
                                           ->otherHours->toTimeWithComma();

            $formData['locality'] = $this->listingItem->locality->name;

            $formData['description'] = $this->listingItem->description;

            $formData['descOtherHours'] = $this->listingItem->descOtherHours;

            $this['itemForm']->setDefaults($formData);
        }
    }

    public function renderEdit($id, $day)
    {
        $this->template->_form = $this['itemForm'];

        $workedHours = null;
        if (!$this->listingItem->isDetached()) {
            $workedHours = $this->listingItem->workedHours->getHours();
        }

        $this->template->itemDate = $this->date;
        $this->template->listing = $this->listing;
        $this->template->workedHours = $workedHours;
        $this->template->defaultWorkedHours = $this->itemUpdateFormFactory
                                                   ->getDefaultTimeValue('workedHours');
    }

    public function handleSearchLocality($term)
    {
        if ($term and mb_strlen($term) >= 3) {
            $this->sendResponse(
                new JsonResponse($this->localityFacade
                                      ->findLocalitiesForAutocomplete($term, 10))
            );
        }
    }

    /**
     * @Actions edit
     */
    protected function createComponentItemForm()
    {
        $form = $this->itemUpdateFormFactory->create();

        $form->onSuccess[] = [$this, 'processSaveItem'];

        return $form;
    }

    public function processSaveItem(Form $form, $values)
    {
        $values['listing'] = $this->listing;
        $values['userID']  = $this->user->id;

        try {
            $listingItem = $this->itemFacade
                                ->saveListingItem($this->listingItem, (array) $values);

        } catch (Runtime\NegativeResultOfTimeCalcException $is) {
            $form->addError(
                'Položku nelze uložit. Musíte mít odpracováno více hodin,
                 než kolik strávíte obědem.'
            );
            return;

        } catch (Runtime\ShiftEndBeforeStartException $e) {
            $form->addError(
                'Nelze skončit směnu dřív než začne. Zkontrolujte si začátek
                 a konec směny.'
            );
            return;

        } catch (Runtime\ListingItemDayAlreadyExistsException $d) {
            $form->addError(
                'Položku nelze uložit, protože výčetka již obsahuje záznam
                 z tohoto dne.'
            );
            return;

        } catch (\DibiException $e) {
            $form->addError('Položka nebyla uložena. Zkuste akci opakovat později.');
            return;
        }

        $this->flashMessage('Položka byla uložena.', 'success');
        $this->redirect(
            'Listing:detail#' . $listingItem->listingItemID,
            ['id' => $this->listing->listingID]
        );
    }

}