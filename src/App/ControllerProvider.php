<?php

namespace App;

use Silex\Api\ControllerProviderInterface;
use Silex\Application as SilexApplication;

class ControllerProvider implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];
        $controllers->post('account/register', 'ctrl.account:create');
        $controllers->post('account/login', 'ctrl.account:login');
        $controllers->patch('account/{uid}', 'ctrl.account:patch');
        $controllers->get('account/{uid}', 'ctrl.account:get');

        return $controllers;
    }
}
