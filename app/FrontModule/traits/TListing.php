<?php

namespace App\FrontModule\Presenters;

use App\Model\Components\IListingDescriptionControlFactory;
use Exceptions\Runtime\ListingNotFoundException;
use App\Model\Facades\ListingFacade;
use Nette\InvalidArgumentException;

trait TListing
{
    /**
     * @var ListingFacade
     * @inject
     */
    public $listingFacade;

    /**
     * @var IListingDescriptionControlFactory
     * @inject
     */
    public $listingDescriptionFactory;

    private function getEntireListingByID($listingID)
    {
        try {
            return $this->listingFacade->getEntireListingByID($listingID);

        } catch (ListingNotFoundException $e) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'error');
            $this->redirect('Listing:overview');
        }
    }

    private function getListingByID($listingID)
    {
        try {
            return $this->listingFacade->getListingByID($listingID);

        } catch (ListingNotFoundException $e) {
            $this->flashMessage('Výčetka nebyla nalezena.', 'error');
            $this->redirect('Listing:overview');
        }

    }

    protected function createComponentListingDescription()
    {
        $desc = $this->listingDescriptionFactory
                     ->create(
                         $this->listing->period,
                         $this->listing->description
                     );
        $desc->setAsClickable(
            'Front:Listing:detail',
            ['id' => $this->listing->listingID]
        );

        return $desc;
    }

    public function setPeriodParametersForFilter($year, $month)
    {
        if ($year === null) {
            $this->redirect(
                'Listing:overview',
                ['year'  => $this->currentDate->format('Y'),
                 'month' => $this->currentDate->format('n')]
            );
        } else {
            try {
                $this['filter']['form']['year']->setDefaultValue($year);
                $this['filter']['form']['month']->setDefaultValue($month);

            } catch (InvalidArgumentException $e) {
                $this->flashMessage(
                    'Lze vybírat pouze z hodnot, které nabízí formulář.',
                    'warning'
                );
                $this->redirect(
                    'Listing:overview',
                    ['year'=>$this->currentDate->format('Y'),
                     'month'=>$this->currentDate->format('n')]
                );
            }
        }
    }
}