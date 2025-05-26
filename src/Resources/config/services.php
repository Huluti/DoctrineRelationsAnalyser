<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Command\AnalyseCommand;
use DoctrineRelationsAnalyserBundle\Command\ValidateCommand;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use DoctrineRelationsAnalyserBundle\Service\RelationshipService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set(HelperService::class);

    $services
        ->set(RelationshipService::class)
        ->arg(0, service(EntityManagerInterface::class))
    ;

    $services
        ->set(AnalyseCommand::class)
        ->arg(0, service(RelationshipService::class))
        ->arg(1, service(Filesystem::class))
        ->tag('console.command');

    $services
        ->set(ValidateCommand::class)
        ->arg(0, service(RelationshipService::class))
        ->arg(1, service(ParameterBagInterface::class))
        ->tag('console.command');
};
