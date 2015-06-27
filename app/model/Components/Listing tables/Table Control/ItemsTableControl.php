<?php

namespace App\Model\Components\ItemsTable;

use App\Model\Components\IListingDescriptionControlFactory;
use App\Model\Entities\ListingItem;
use Nette\Application\UI\Control;
use App\Model\Facades\ItemFacade;
use App\Model\Time\TimeUtils;

class ItemsTableControl extends Control
{
    /**
     * @var IListingDescriptionControlFactory
     */
    private $listingDescriptionControlFactory;

    /**
     * @var ItemFacade
     */
    private $itemFacade;

    /**
     * @var \DateTime
     */
    private $period;

    /**
     * @var ListingItem[]
     */
    private $items = array();


    /* *** OPTIONS ** */

    private $showActions;
    private $parameters = array();

    private $showCheckBoxes = false;
    private $isTableCaptionVisible = false;

    private $tableCaptionDescription;
    private $workedDays;
    private $totalWorkedHours;

    public function __construct(
        \DateTime $period,
        IListingDescriptionControlFactory $listingDescriptionControlFactory,
        ItemFacade $itemFacade
    ) {
        $this->listingDescriptionControlFactory = $listingDescriptionControlFactory;
        $this->period = $period;
        $this->itemFacade = $itemFacade;
    }

    /**
     * @param ListingItem[] $listingItems
     */
    public function setListingItems(array $listingItems)
    {
        $this->items = $this->itemFacade
                            ->createListingItemDecoratorsCollection(
                                $listingItems,
                                $this->period->format('Y'),
                                $this->period->format('n')
                            );
    }

    protected function createComponentDescription()
    {
        $comp = $this->listingDescriptionControlFactory->create(
            $this->period,
            $this->tableCaptionDescription
        );

        return $comp;
    }

    public function showTableCaption(
        $description,
        $workedDays,
        $totalWorkedHours,
        $destination = null,
        array $params = []
    ) {
        $this->tableCaptionDescription = $description;
        $this->workedDays = $workedDays;
        $this->totalWorkedHours = $totalWorkedHours;

        if ($destination !== null) {
            $this['description']->setAsClickable($destination, $params);
        }

        $this->isTableCaptionVisible = true;
    }

    public function showActions($path, array $parameters = null)
    {
        /*if (!$path instanceof Template) {
            throw new InvalidArgumentException('chybka');
        }*/

        $this->parameters = $parameters;
        $this->showActions = $path;
    }

    public function showCheckBoxes()
    {
        $this->showCheckBoxes = true;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/table.latte');

        if (!$this->presenter->isAjax()) {
            $this->items = $this->itemFacade
                                ->generateListingItemDecoratorsForEntireTable(
                                    $this->items,
                                    $this->period
                                );
        }

        $template->itemsCollection = $this->items;

        $template->workedDays = $this->workedDays;
        $template->totalWorkedHours = $this->totalWorkedHours;

        $template->isTableCaptionVisible = $this->isTableCaptionVisible;
        $template->showCheckBoxes = $this->showCheckBoxes;
        $template->showActions = $this->showActions;
        $template->parameters = $this->parameters;
        $template->numberOfDaysInMonth = TimeUtils::getNumberOfDaysInMonth(
            $this->period->format('Y'),
            $this->period->format('n')
        );

        $template->render();
    }

}