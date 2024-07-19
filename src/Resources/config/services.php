<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Command\AnalyseCommand;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use Symfony\Component\Filesystem\Filesystem;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(HelperService::class);

    $services
        ->set(AnalyseCommand::class)
        ->arg(0, service(EntityManagerInterface::class))
        ->arg(1, service(Filesystem::class))
        ->tag('console.command');
};
