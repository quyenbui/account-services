<?php

namespace App;

use App\Account\AccountController;
use App\Account\AccountDataService;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $pimple)
    {
        $pimple->register(new DoctrineServiceProvider());
        $pimple->register(new ServiceControllerServiceProvider());
        $pimple->register(new ValidatorServiceProvider());

        $pimple['password'] = function () {
            return new BCryptPasswordEncoder(10);
        };

        $pimple['data.account'] = function () use ($pimple) {
            return new AccountDataService($pimple);
        };

        $pimple['ctrl.account'] = function () use ($pimple) {
            return new AccountController($pimple);
        };
    }
}
