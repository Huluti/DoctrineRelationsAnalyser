<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Command;

use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
use DoctrineRelationsAnalyserBundle\Enum\Level;
use DoctrineRelationsAnalyserBundle\Service\RelationshipService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'doctrine-relations-analyser:validate',
    description: 'Command to validate Doctrine relationships between entities against configuration file of bundle.',
)]
class ValidateCommand extends Command
{
    public function __construct(
        private readonly RelationshipService $relationshipService,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $io = new SymfonyStyle($input, $output);
        $io->title('Doctrine Relations Analyzer - Validation');

        $config = $this->params->get('doctrine_relations_analyser.entities');
        $entities = array_keys($config);

        $relationships = $this->relationshipService->fetch($entities, AnalysisMode::ALL);
        if (empty($relationships)) {
            $io->error('No relationships detected');

            return Command::FAILURE;
        }

        $hasErrors = false;

        foreach ($config as $entityClass => $entityConfig) {
            if (empty($entityConfig['relations'])) {
                $io->text("No relations defined in config for entity: <info>{$entityClass}</info>");
                continue;
            }

            foreach ($entityConfig['relations'] as $relationName => $relationConfig) {
                $targetClass = $relationConfig['class'] ?? null;
                $expectedDeletion = $relationConfig['deletion'] ?? false;
                $expectedDeletionLevel = $relationConfig['deletion_type'] ?? null;

                if (!isset($relationships[$entityClass])) {
                    $io->error("Entity <comment>{$entityClass}</comment> not found in relationships.");
                    $hasErrors = true;
                    continue;
                }

                if (!isset($relationships[$entityClass][$targetClass])) {
                    $io->error("Relation to <comment>{$targetClass}</comment> not found for <comment>{$entityClass}</comment>.");
                    $hasErrors = true;
                    continue;
                }

                $relation = $relationships[$entityClass][$targetClass];

                // Validate deletions if expected
                if ($expectedDeletion) {
                    $expectedLevelEnum = null;
                    if ('orm' === $expectedDeletionLevel) {
                        $expectedLevelEnum = Level::ORM;
                    } elseif ('database' === $expectedDeletionLevel) {
                        $expectedLevelEnum = Level::DATABASE;
                    } else {
                        $io->error("Invalid deletion_type '<comment>{$expectedDeletionLevel}</comment>' in config. Must be 'orm' or 'database'.");
                        $hasErrors = true;
                        continue;
                    }

                    $found = false;

                    foreach ($relation['deletions'] as $deletion) {
                        if (
                            $deletion['level'] === $expectedLevelEnum
                            && in_array(strtolower($deletion['type']->name), ['orphan_removal', 'cascade'], true)
                        ) {
                            $found = true;
                            $io->success("Matched deletion type '<info>{$deletion['type']->name}</info>' with level '<info>{$deletion['level']->name}</info>' for <info>{$entityClass} -> {$targetClass}</info>");
                            break;
                        }
                    }

                    if (!$found) {
                        $io->error("Expected deletion at level '<comment>{$expectedLevelEnum->name}</comment>' not found for <comment>{$entityClass} -> {$targetClass}</comment>.");
                        $hasErrors = true;
                    }
                } else {
                    $io->text("No deletion expected for relation <info>{$entityClass} -> {$targetClass}</info>.");
                }
            }
        }

        $end = microtime(true);
        $elapsed = round($end - $start, 3);

        if ($hasErrors) {
            $io->error("Validation completed in {$elapsed}s with errors.");

            return Command::FAILURE;
        }

        $io->success("Relationship analysis completed successfully in {$elapsed}s.");

        return Command::SUCCESS;
    }
}
