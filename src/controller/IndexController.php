<?php

namespace SearchEngine\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class IndexController
{

    public function indexAction(Request $request, Application $app) {
        return $app['twig']->render('hello.twig', [
            'msg' => $request->attributes->get('name', 'John')
        ]);
    }

}