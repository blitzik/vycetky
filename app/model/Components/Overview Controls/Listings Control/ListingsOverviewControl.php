<?php

namespace App\Model\Components;

use Nextras\Application\UI\SecuredLinksControlTrait;
use Nette\Application\UI\Multiplier;
use App\Model\Facades\ListingFacade;
use Nette\Application\UI\Control;
use App\Model\Entities\Listing;
use Nextras\Datagrid\Datagrid;
use blitzik\Arrays\Arrays;
use Nette\Security\User;

class ListingsOverviewControl extends Control
{
    use SecuredLinksControlTrait;
    use TOverviewParametersValidators;

    /**
     * @var ListingFacade
     */
    private $listingFacade;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Listing[]
     */
    private $listings;

    private $filterParams;

    private $heading;


    public function __construct(
        //array $listings,
        User $user,
        ListingFacade $listingFacade
    ) {
        //$this->checkFilterParameters($filterParams);

        //$this->filterParams = $filterParams;
        $this->user = $user;
        $this->listingFacade = $listingFacade;

        //$this->listings = $listings;
        /*$this->listings = $this->listingFacade
                               ->findListingsByPeriod(
                                   $filterParams['year'],
                                   $filterParams['month']
                               );*/
    }

    /**
     * @param array $listings
     */
    public function setListings(array $listings)
    {
        $this->listings = $listings;
    }

    protected function createComponentDataGrid()
    {
        return new Multiplier(function ($month) {
            $grid = new Datagrid();
            $grid->addColumn('year', 'Rok');
            $grid->addColumn('month', 'Měsíc');
            $grid->addColumn('description', 'Popis');
            $grid->addColumn('workedDays', 'Dny');
            $grid->addColumn('totalWorkedHours', 'Hodiny');

            $grid->setDataSourceCallback(function ($filter, $order) use ($month) {
                return $this->listings[$month];
            });

            $grid->addCellsTemplate(__DIR__ . '/templates/grid/grid.latte');

            return $grid;
        });
    }

    /* * * * * * OPTIONS * * * * * */

    public function setHeading($heading)
    {
        $this->heading = $heading;
    }

    public function render()
    {
        $template = $this->getTemplate();
        $template->setFile(__DIR__ . '/templates/template.latte');

        if ($this->presenter->getParameter('month') !== null) {
            $this->template->date = new \DateTime($this->presenter->getParameter('year') . '-' .
                                                  $this->presenter->getParameter('month') . '-01');
        }

        $template->heading = $this->heading;
        $template->numberOfListings = isset($this->listings) ? Arrays::count_recursive($this->listings, 1) : 0;
        $template->listings = $this->listings;

        $template->render();
    }
}