<?php

namespace App;

use Nette;
use Nette\Application\IRouter;
use Nette\Application\Request;

class MyRouter implements IRouter
{
    /**
     * Maps HTTP request to a Request object.
     * @return Request|NULL
     */
    function match(Nette\Http\IRequest $httpRequest)
    {
        dump($httpRequest->getUrl());
        $path = explode('/', $httpRequest->getUrl()->getRelativeUrl());

        dump($path);
    }


    /**
     * Constructs absolute URL from Request object.
     * @return string|NULL
     */
    function constructUrl(Request $appRequest, Nette\Http\Url $refUrl)
    {

    }

}