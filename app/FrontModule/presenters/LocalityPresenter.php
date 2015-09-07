<?php

namespace App\FrontModule\Presenters;

use Nette\Application\Responses\JsonResponse;
use Nette\Forms\Controls\SubmitButton;
use App\Model\Facades\LocalityFacade;
use App\Model\Entities\Locality;
use Nette\Application\UI\Form;
use Tracy\Debugger;

class LocalityPresenter extends SecurityPresenter
{
    const VISIBLE_LOCALITIES = 25;

    /**
     * @persistent
     */
    public $search;

    /**
     * @var LocalityFacade
     * @inject
     */
    public $localityFacade;

    /**
     * @var Locality[]
     */
    private $localities;

    public function actionItemAutocomplete($search)
    {
        $this['localitiesTableForm']['search']->setDefaultValue($search);
    }
    
    public function renderItemAutocomplete($search)
    {
        if (!isset($this->localities)) {
                $this->localities = $this->localityFacade->findLocalities($search, self::VISIBLE_LOCALITIES);
        }

        $this->template->_form = $this['localitiesTableForm'];
        $this->template->localities = $this->localities;
        $this->template->numberOfLocalities = $this->localityFacade->getNumberOfUserLocalities();
    }

    /**
     * @Actions itemAutocomplete
     */
    protected function createComponentLocalitiesTableForm()
    {
        $form = new Form();
        $form->getElementPrototype()->id = 'localities-form';

        $form->addText('search', 'Filtr:', 10)
                ->setHtmlId('search');

        $form->addSubmit('hide', 'Odebrat označené')
                ->setAttribute('class', 'submitButton')
                ->onClick[] = [$this, 'processHide'];

        $form->addSubmit('filter', 'Vyhledej')
                ->setAttribute('class', 'submitButton')
                ->onClick[] = [$this, 'processFilter'];

        $form->addProtection();

        //$form->getElementPrototype()->class = "ajax";

        return $form;
    }

    public function processHide(SubmitButton $button)
    {
        $localitiesIDs = $button->getForm()->getHttpData(Form::DATA_TEXT, 'lcls[]');
        $values = $button->getForm()->getValues();

        $this->localityFacade->removeLocalities($localitiesIDs);
        $this->flashMessage('Pracoviště byla úspěšně odstraněna z nápovědy.', 'success');

        if ($this->isAjax()) {
            $locality = isset($this->search) ? $this->search : $values['search'];
            $this->localities = $this->localityFacade
                                     ->findLocalities(
                                         $locality,
                                         self::VISIBLE_LOCALITIES
                                     );

            $this->redrawControl('flashMessages');
            $this->redrawControl('localitiesList');
        } else {
            $this->redirect('this');
        }
    }

    public function processFilter(SubmitButton $button)
    {
        $values = $button->getForm()->getValues();
        $this->localities = $this->localityFacade->findLocalities($values['search'], self::VISIBLE_LOCALITIES);
        $this->search = $values['search'];

        if ($this->isAjax()) {
            $this->redrawControl('localitiesList');
        } else {
            $this->redirect('Locality:itemAutocomplete');
        }
    }

    /**
     * @Actions ItemAutocomplete
     */
    public function handleSearchLocalities($search)
    {
        $this->localities = $this->localityFacade->findLocalities($search, self::VISIBLE_LOCALITIES);
        $this->search = $search;

        if ($this->isAjax()) {
            $this->redrawControl('localitiesList');
        } else {
            $this->redirect('this');
        }
    }

    /**
     * @secured
     */
    public function handleDoNotShowLocality($localityID, $search)
    {
        $this->localityFacade->removeUserLocality($localityID);
        $this->flashMessage('Pracoviště bylo úspěšně odstraněno z nápovědy.', 'success');

        if ($this->isAjax()) {
            $this->localities = $this->localityFacade->findLocalities($search, self::VISIBLE_LOCALITIES);

            $this->redrawControl('flashMessages');
            $this->redrawControl('localitiesList');
        } else {
            $this->redirect('this');
        }
    }

}