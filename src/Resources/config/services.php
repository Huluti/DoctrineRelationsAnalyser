<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Command\AnalyseCommand;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services
        ->set(AnalyseCommand::class)
        ->arg(0, service(EntityManagerInterface::class))
        ->tag('console.command');
};
