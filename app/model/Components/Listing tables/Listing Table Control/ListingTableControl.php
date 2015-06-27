<?php

namespace App\Model\Components\ListingTable;

use App\Model\Components\ItemsTable\IItemsTableControlFactory;
use Exceptions\Runtime\DayExceedCurrentMonthException;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Exceptions\Runtime\ListingItemNotFoundException;
use Exceptions\Runtime\ListingNotFoundException;
use App\Model\Facades\ListingFacade;
use App\Model\Entities\ListingItem;
use App\Model\Facades\UserManager;
use App\Model\Facades\ItemFacade;
use Nette\Application\UI\Control;
use App\Model\Entities\Listing;
use Nette\Security\User;

class ListingTableControl extends Control
{
    use SecuredLinksControlTrait;

    /**
     * @var IItemsTableControlFactory
     */
    private $itemsTableControlFactory;

    /**
     * @var ListingFacade
     */
    private $listingFacade;

    /**
     * @var ItemFacade
     */
    private $itemFacade;

    /**
     * @var User
     */
    private $user;


    /**
     * @var ListingItem[]
     */
    private $itemsCollection;

    /**
     * @var Listing
     */
    private $listing;


    public function __construct(
        Listing $listing,
        IItemsTableControlFactory $itemsTableControlFactory,
        ListingFacade $listingFacade,
        ItemFacade $itemFacade,
        User $user
    ) {
        $this->listing = $listing;

        $this->itemsTableControlFactory = $itemsTableControlFactory;
        $this->listingFacade = $listingFacade;
        $this->itemFacade = $itemFacade;
        $this->user = $user;
    }

    protected function createComponentItemsTable()
    {
        $comp = $this->itemsTableControlFactory->create($this->listing->period);
        $comp->showActions(
            __DIR__ . '/templates/actions.latte',
            ['listingID' => $this->listing->listingID]
        );

        $comp->showTableCaption(
            $this->listing->description,
            $this->listing->workedDays,
            $this->listing->totalWorkedHours
        );

        return $comp;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        if (!isset($this->itemsCollection)) {
            $this->itemsCollection = $this->listing->listingItems;
        }

        $this['itemsTable']->setListingItems($this->itemsCollection);

        $template->listing = $this->listing;

        $template->render();
    }

    /**
     * @secured
     */
    public function handleRemoveItem($day)
    {
        $noDays = $this->listing->getDaysInMonth();
        if (!is_numeric($day) or !($day >= 1 and $day <= $noDays))
            $this->redirect('this');

        try {
            $this->itemFacade->removeListingItem($day, $this->listing);

            if ($this->presenter->isAjax()) {
                $this->listing = $this->listingFacade
                                      ->getEntireListingByID($this->listing->listingID);

                $listingItem = new ListingItem(); // empty item for decorator
                $listingItem->setDay($day);

                $this->itemsCollection = [$listingItem];

                $this['itemsTable']->redrawControl();
            } else {
                $this->flashMessage('Řádek byl vymazán.', 'success');
                $this->redirect('this');
            }

        } catch (ListingNotFoundException $lnf) {
            $this->flashMessage('Výčetka, kterou se snažíte upravit, nebyla nalezena.');
            $this->redirect('Listing:overview');
        }
    }

    /**
     * @param int $day Numeric representation of the day of the month
     * @secured
     */
    public function handleCopyItem($day)
    {
        $noDays = $this->listing->getDaysInMonth();
        if (!is_numeric($day) or !($day >= 1 and $day <= $noDays))
            $this->redirect('this');

        try {
            $newListingItem = $this->itemFacade
                                   ->shiftCopyOfListingItemDown(
                                       $day,
                                       $this->listing
                                   );

        } catch (ListingItemNotFoundException $is) {
            $this->flashMessage(
                'Řádek výčetky nemohl být zkopírován, protože nebyl nalezen.',
                'error'
            );
            $this->redirect('this');

        } catch (DayExceedCurrentMonthException $is) {
            $this->flashMessage(
                'Nelze vytvořit kopii poslední položky ve výčetce.',
                'error'
            );
            $this->redirect('this');

        } catch (\DibiException $e) {
            $this->flashMessage(
                'Kopie výčetky nemohla být založena.
                 Zkuste akci opakovat později.',
                'error'
            );
            $this->redirect('this');
        }

        if ($this->presenter->isAjax()) {
            $this->listing = $this->listingFacade
                                  ->getEntireListingByID($this->listing->listingID);

            $this->itemsCollection = [$newListingItem];

            $this['itemsTable']->redrawControl();
        } else {
            $this->flashMessage('Řádek byl zkopírován.', 'success');
            $this->redirect('this#' . $newListingItem->listingItemID);
        }
    }

}