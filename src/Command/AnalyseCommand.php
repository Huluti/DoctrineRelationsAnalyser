<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
use DoctrineRelationsAnalyserBundle\Enum\DeletionType;
use DoctrineRelationsAnalyserBundle\Enum\Level;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use Graphp\Graph\Graph;
use Graphp\GraphViz\GraphViz;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use ValueError;

#[AsCommand(
    name: 'doctrine-relations-analyser:analyse',
    description: 'Command to visualise easily the relationships between entities',
)]
class AnalyseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entities', null, InputOption::VALUE_OPTIONAL, 'Comma-separated list of entities to analyze')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Analysis mode: all, deletions', AnalysisMode::ALL->value, AnalysisMode::cases())
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output path for reports generated')
            ->addOption('graph', null, InputOption::VALUE_NONE, 'Generate Graphviz graph')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $io = new SymfonyStyle($input, $output);

        try {
            $mode = AnalysisMode::from($input->getOption('mode'));
        } catch (ValueError $e) {
            $io->error('Invalid mode. Allowed values are: all, deletions.');

            return Command::FAILURE;
        }

        $io->section('Analysis mode: ' . $mode->value);

        $entitiesOption = $input->getOption('entities');
        $entitiesToAnalyze = $entitiesOption ? explode(',', $entitiesOption) : [];
        $restrictedEntities = !empty($entitiesToAnalyze);
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $relationships = [];

        foreach ($metaData as $meta) {
            $className = $meta->getName();
            if ($restrictedEntities && !in_array($className, $entitiesToAnalyze, true)) {
                continue; // Skip entities not in the list
            }

            $relationships[$className] = [];
            foreach ($meta->associationMappings as $fieldName => $association) {
                $relationDetails = [
                    'field' => $fieldName,
                    'targetEntity' => $association['targetEntity'],
                    'type' => $association['type'],
                ];

                if (AnalysisMode::DELETIONS === $mode) {
                    $deletions = [];

                    if (isset($association['orphanRemoval']) && $association['orphanRemoval']) {
                        $deletions[] = [
                            'type' => DeletionType::ORPHAN_REMOVAL,
                            'level' => Level::ORM,
                            'value' => 'true',
                        ];
                    }

                    if (isset($association['cascade']) && in_array('remove', $association['cascade'], true)) {
                        $deletions[] = [
                            'type' => DeletionType::CASCADE,
                            'level' => Level::ORM,
                            'value' => '["remove"]',
                        ];
                    }

                    if (!empty($association['joinColumns'])) {
                        if (!empty($association['joinColumns'][0]['onDelete'])) {
                            $deletions[] = [
                                'type' => DeletionType::ON_DELETE,
                                'level' => Level::DATABASE,
                                'value' => $association['joinColumns'][0]['onDelete'],
                            ];
                        }
                    }

                    $relationDetails['deletions'] = $deletions;
                }

                $relationships[$className][] = $relationDetails;
            }
        }

        if (empty($relationships)) {
            $io->error('No relationships detected');

            return Command::FAILURE;
        }

        $this->outputRelationships($relationships, $io, $mode);

        $outputPath = $input->getOption('output');
        if ($outputPath) {
            $outputPath = ltrim($outputPath, '/');

            try {
                // Ensure $outputPath exists, create it if it doesn't
                if (!$this->filesystem->exists($outputPath)) {
                    $this->filesystem->mkdir($outputPath);
                }
            } catch (IOExceptionInterface $e) {
                $io->error("Can't create folder: " . $e->getMessage());

                return Command::FAILURE;
            }
        }

        if ($input->getOption('graph')) {
            if ($outputPath) {
                if ($this->generateGraph($relationships, $outputPath, $mode)) {
                    $io->success("Graph image generated in: $outputPath");
                } else {
                    $io->error("Can't save graph image");

                    return Command::FAILURE;
                }
            } else {
                $io->error('Graph option requires setting output folder');

                return Command::FAILURE;
            }
        }

        $end = microtime(true);
        $elapsed = round($end - $start, 3);

        $io->success("Relationship analysis completed in $elapsed seconds.");

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $relationships
     */
    private function outputRelationships(array $relationships, SymfonyStyle $io, AnalysisMode $mode): void
    {
        foreach ($relationships as $entity => $relations) {
            $io->section("Entity: $entity");
            foreach ($relations as $relation) {
                $io->text("Field: {$relation['field']}");
                $io->text("Target Entity: {$relation['targetEntity']}");
                $io->text('Type: ' . HelperService::getRelationType($relation['type']));

                if (AnalysisMode::DELETIONS === $mode) {
                    if (!empty($relation['deletions'])) {
                        $io->text('Deletions properties:');

                        foreach ($relation['deletions'] as $deletion) {
                            $level = Level::ORM === $deletion['level'] ? 'ORM' : 'Database';
                            $io->text("- {$deletion['type']->value}: {$deletion['value']} ($level level)");
                        }
                    }
                }

                $io->newLine();
            }
        }
    }

    /**
     * @param array<mixed> $relationships
     */
    private function generateGraph(array $relationships, string $outputPath, AnalysisMode $mode): bool
    {
        $graph = new Graph();
        $graph->setAttribute('graphviz.graph.rankdir', 'LR');

        // Create nodes for entities
        $nodes = [];
        foreach (array_keys($relationships) as $entity) {
            $vertex = $graph->createVertex();
            $vertex->setAttribute('id', $entity);
            $nodes[$entity] = $vertex;
        }

        // Create edges for relationships
        foreach ($relationships as $entity => $relations) {
            foreach ($relations as $relation) {
                $targetEntity = $relation['targetEntity'];
                if (isset($nodes[$targetEntity])) {
                    if (AnalysisMode::ALL === $mode) {
                        $edge = $graph->createEdgeDirected($nodes[$entity], $nodes[$targetEntity]);
                        $edge->setAttribute('graphviz.label', HelperService::getRelationType($relation['type']));
                    } elseif (AnalysisMode::DELETIONS === $mode) {
                        foreach ($relation['deletions'] as $deletion) {
                            $invertArrow = DeletionType::ON_DELETE === $deletion['type'] ? true : false;
                            if ($invertArrow) {
                                // Arrow points from parent (entity) to child (targetEntity)
                                $edge = $graph->createEdgeDirected($nodes[$targetEntity], $nodes[$entity]);
                            } else {
                                // Arrow points from child (targetEntity) to parent (entity)
                                $edge = $graph->createEdgeDirected($nodes[$entity], $nodes[$targetEntity]);
                            }
                            $label = "{$deletion['type']->value}: {$deletion['value']}";
                            $edge->setAttribute('graphviz.label', $label);
                            if (Level::ORM === $deletion['level']) {
                                $edge->setAttribute('graphviz.color', 'blue');
                            } else {
                                $edge->setAttribute('graphviz.color', 'red');
                            }
                        }
                    }
                }
            }
        }

        $format = 'png';
        $graphviz = new GraphViz();
        $graphviz->setFormat($format);
        $imageData = $graphviz->createImageData($graph);

        $fullPath = $outputPath . '/' . $mode->value . '.' . $format;
        try {
            $this->filesystem->dumpFile($fullPath, $imageData);
        } catch (IOExceptionInterface) {
            return false;
        }

        return true;
    }
}
