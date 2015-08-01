<?php

namespace App\UserModule\Presenters;

use App\Model\Facades\UserManager;
use Nette\Application\UI\Form;
use Nette;

class AccountPresenter extends BasePresenter
{
    /**
    * @var UserManager
    * @inject
    */
    public $userManager;

    /**
    * @var \Nette\Http\IRequest
    * @inject
    */
    public $httpRequest;

    /**
     * @var \App\Model\Entities\Invitation
     */
    private $invitation;


    /*
     * --------------------
     * ----- Default -----
     * --------------------
     */

    public function actionDefault()
    {

    }

    public function renderDefault()
    {
    }

    protected function createComponentLoginForm()
    {
        $form = new Form();

        $form->addText('email', 'E-mailová adresa uživatele:', 25, 70)
                ->setRequired('Zadejte prosím svůj E-mail.')
                ->addRule(Form::EMAIL, 'Zadejte prosím E-mailovou adresu ve správném formátu.');

        $form->addPassword('pass', 'Heslo:', 25)
                ->setRequired('Zadejte prosím své heslo.')
                ->addRule(Form::FILLED, 'Zadejte vaše heslo prosím.');

        $form->addCheckbox('keepLogin', 'Zůstat přihlášen')
                ->setDefaultValue(true);

        $form->addSubmit('login', 'Přihlásit')
                ->setOmitted();

        $form->onSuccess[] = callback($this, 'processLoginForm');

        return $form;

    }

    public function processLoginForm(Form $form)
    {
        $values = $form->getValues();

        try{
           $this->user->login($values['email'], $values['pass']);

           if ($values['keepLogin']) {
               $this->user->setExpiration('+30 days', false);
           } else {
               $this->user->setExpiration('+1 hour', true);
           }

           $currentDate = new \DateTime('now');
           $this->redirect(
               ':Front:Listing:overview',
               ['year' => $currentDate->format('Y'),
                'month' => $currentDate->format('n')]
               );

        } catch (\Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
            return;
        }
    }

    /*
     * ------------------------
     * ----- REGISTRATION -----
     * ------------------------
     */

    public function actionRegistration($token)
    {
        try {
                $this->invitation = $this->userManager->checkToken($token);

        } catch (\Exceptions\Runtime\TokenValidityException $t) {
            $this->flashMessage('Registrovat se může pouze uživatel s platnou pozvánkou.', 'error');
            $this->redirect('Account:default');
        }

        $this['registrationForm']['email']->setDefaultValue($this->invitation->email);
    }


    public function renderRegistration($token)
    {
    }

    protected function createComponentRegistrationForm()
    {
        $form = new Form();

        $form->addText('username', 'Uživatelské jméno:', null, 25)
                ->setRequired('Vyplňte své uživatelské jméno prosím.')
                ->setAttribute('placeholder', 'Vyplňte jméno');

        $form->addpassword('password', 'Uživatelské heslo:')
                ->setRequired('Vyplňte své heslo prosím.')
                ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaků.', 5)
                ->setAttribute('placeholder', 'Vyplňte heslo')
                ->setHtmlId('password-input');

        $form->addPassword('pass2', 'Kontrola hesla:')
                ->setRequired('Vyplňte kontrolu vašeho hesla prosím.')
                ->addRule(Form::EQUAL, 'Zadaná hesla se musí shodovat.', $form['password'])
                ->setOmitted()
                ->setAttribute('placeholder', 'Zadejte heslo znovu')
                ->setHtmlId('password-control-input');

        $form->addText('email', 'E-mail:')
                ->getControlPrototype()->readonly = 'readonly';
                /*->setRequired('Vyplňte váš email prosím.')
                ->addRule(Form::EMAIL, 'Zadejte prosim platný formát E-mailové adresy.');*/

        $form->addSubmit('reg', 'Zaregistrovat uživatele')
                ->setOmitted();

        $form->onSuccess[] = callback($this, 'processUserRegistration');

        return $form;

    }

    public function processUserRegistration(Form $form)
    {
        $values = $form->getValues();
        $forbiddenNames = array_flip(['systém', 'system', 'admin', 'administrator',
                                      'administrátor']);

        if (array_key_exists(strtolower($values['username']), $forbiddenNames)) {
            $form->addError('Vámi zadané jméno nelze použít. Vyberte si prosím jiné.');
            return;
        }

        $values['ip']        = $this->httpRequest->getRemoteAddress();
        $values['role']      = 'employee';
        $values['lastIP']    = $values['ip'];
        $values['lastLogin'] = new \Nette\Utils\DateTime();

        $user = new \App\Model\Entities\User;
        $user->assign($values);

        try {
            $this->userManager->registerNewUser($user, $this->invitation);

            $this->flashMessage('Váš účet byl vytvořen. Nyní se můžete přihlásit.', 'success');
            $this->redirect('Account:default');

        } catch (\Exceptions\Runtime\InvalidUserInvitationEmailException $iu) {
            $form->addError('Vámi zadaný E-mail se neshoduje s E-mailem, na který byla odeslána pozvánka!');

        } catch (\Exceptions\Runtime\DuplicateUsernameException $du) {
            $form->addError('Vámi zvolené jméno vužívá již někdo jiný. Vyberte si prosím jiné jméno.');

        } catch (\Exceptions\Runtime\DuplicateEmailException $de) {
            $form->addError("Zadejte prosím jiný E-mail.");

        } catch (\DibiException $d) {
            $form->addError('Registraci nelze dokončit. Zkuste to prosím později.');
        }

    }

}