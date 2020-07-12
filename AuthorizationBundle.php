<?php

namespace Ybenhssaien\AuthorizationBundle;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Ybenhssaien\AuthorizationBundle\Command\DebugAuthorizationCommand;

class AuthorizationBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        $application->add(new DebugAuthorizationCommand());
    }

    public function build(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources'));
        $loader->load('services.yaml');
    }
}
