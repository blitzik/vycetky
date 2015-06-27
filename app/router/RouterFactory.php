<?php

namespace App;

use	Nette\Application\Routers\RouteList;
use	Nette\Application\Routers\Route;
use Nette;

class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

        $router[] = new Route('user/<presenter>/<action>[[/<email>]/<token>]', array(
                    'module' => 'User',
                    'presenter' => 'Account',
                    'action' => 'default',
                    "email" => null,
                    'token' => null,
                ));

        $router[] = new Route('<presenter>/<action>[/<id \d+>]', array(
                    'module' => 'Front',
                    'presenter' => 'Listing',
                    'action' => 'overview',
                    'id' => null,
                ));

		return $router;
	}
}