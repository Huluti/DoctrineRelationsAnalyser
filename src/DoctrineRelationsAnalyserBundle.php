<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DoctrineRelationsAnalyserBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('entities')
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('relations')
                                ->useAttributeAsKey('field')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('class')->isRequired()->end()
                                        ->booleanNode('deletion')->defaultFalse()->end()
                                        ->enumNode('deletion_type')
                                            ->values(['orm', 'database', null])
                                            ->defaultNull()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('Resources/config/services.php');

        $entities = $config['entities'] ?? [];
        $builder->setParameter('doctrine_relations_analyser.entities', $entities);
    }
}
