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
        array $filterParams,
        User $user,
        ListingFacade $listingFacade
    ) {
        $this->checkFilterParameters($filterParams);

        $this->filterParams = $filterParams;
        $this->user = $user;
        $this->listingFacade = $listingFacade;

        $this->listings = $this->listingFacade
                               ->findListingsByPeriod(
                                   $filterParams['year'],
                                   $filterParams['month']
                               );
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

        if (isset($this->filterParams['month'])) {
            $this->template->date = new \DateTime($this->filterParams['year'] . '-' .
                                                  $this->filterParams['month'] . '-01');
        }

        $template->heading = $this->heading;
        $template->numberOfListings = Arrays::count_recursive($this->listings, 1);
        $template->listings = $this->listings;

        $template->render();
    }
}