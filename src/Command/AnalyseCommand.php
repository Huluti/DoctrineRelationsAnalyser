<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
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

            foreach ($meta->associationMappings as $fieldName => $association) {
                $relationDetails = [
                    'field' => $fieldName,
                    'targetEntity' => $association['targetEntity'],
                    'type' => $association['type'],
                ];

                if (AnalysisMode::DELETIONS === $mode) {
                    $deletions = [];

                    if (isset($association['orphanRemoval']) && $association['orphanRemoval']) {
                        $deletions['orphanRemoval'] = true;
                    }

                    if (isset($association['cascade']) && in_array('remove', $association['cascade'], true)) {
                        $deletions['cascade'] = true;
                    }

                    if (isset($association['joinColumns']) && !empty($association['joinColumns'])) {
                        if (isset($association['joinColumns'][0]['onDelete']) && !empty($association['joinColumns'][0]['onDelete'])) {
                            $deletions['onDelete'] = $association['joinColumns'][0]['onDelete'];
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

    private function outputRelationships(array $relationships, SymfonyStyle $io, AnalysisMode $mode): void
    {
        foreach ($relationships as $entity => $relations) {
            $io->section("Entity: $entity");
            foreach ($relations as $relation) {
                $io->text("Field: {$relation['field']}");
                $io->text("Target Entity: {$relation['targetEntity']}");
                $io->text('Type: ' . $this->getRelationType($relation['type']));

                if (AnalysisMode::DELETIONS === $mode) {
                    if (!empty($relation['deletions'])) {
                        $io->text('Deletions properties:');

                        if (isset($relation['deletions']['onDelete'])) {
                            $io->text("- onDelete: {$relation['deletions']['onDelete']}");
                        }
                        if (isset($relation['deletions']['orphanRemoval'])) {
                            $io->text('- orphanRemoval: true');
                        }
                        if (isset($relation['deletions']['cascade'])) {
                            $io->text("- cascade: ['remove']");
                        }
                    }
                }

                $io->newLine();
            }
        }
    }

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
                        $edge->setAttribute('graphviz.label', $this->getRelationType($relation['type']));
                    } elseif (AnalysisMode::DELETIONS === $mode) {
                        foreach ($relation['deletions'] as $key => $value) {
                            if ('onDelete' === $key) {
                                $label = "onDelete: {$value}";
                            } elseif ('orphanRemoval' === $key) {
                                $label = 'orphanRemoval: true';
                            } elseif ('cascade' === $key) {
                                $label = "cascade: \'remove\'";
                            }
                            if (isset($label)) {
                                $edge = $graph->createEdgeDirected($nodes[$entity], $nodes[$targetEntity]);
                                $edge->setAttribute('graphviz.label', $label);
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

    private function getRelationType(int $type): string
    {
        return match ($type) {
            \Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_ONE => 'OneToOne',
            \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_ONE => 'ManyToOne',
            \Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_MANY => 'OneToMany',
            \Doctrine\ORM\Mapping\ClassMetadata::MANY_TO_MANY => 'ManyToMany',
            default => 'Unknown',
        };
    }
}
