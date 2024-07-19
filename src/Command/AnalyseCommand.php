<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Command;

use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
use DoctrineRelationsAnalyserBundle\Enum\DeletionType;
use DoctrineRelationsAnalyserBundle\Enum\GraphFormat;
use DoctrineRelationsAnalyserBundle\Enum\Level;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use DoctrineRelationsAnalyserBundle\Service\RelationshipService;
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
        private readonly RelationshipService $relationshipService,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('entities', null, InputOption::VALUE_REQUIRED, 'Comma-separated list of entities to analyze')
            ->addOption('mode', 'm', InputOption::VALUE_REQUIRED, 'Analysis mode', AnalysisMode::ALL->value, array_column(AnalysisMode::cases(), 'name'))
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output path for reports generated')
            ->addOption('graph', 'g', InputOption::VALUE_NONE, 'Generate Graphviz graph')
            ->addOption('graph-format', null, InputOption::VALUE_REQUIRED, 'Graph image format', GraphFormat::PNG->value, array_column(GraphFormat::cases(), 'name'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $io = new SymfonyStyle($input, $output);

        $io->title('Doctrine Relations Analyzer');

        // Validate options
        try {
            $modeOption = AnalysisMode::from($input->getOption('mode'));
        } catch (ValueError $e) {
            $io->error('Invalid mode. Allowed values are: ' . implode(',', array_column(AnalysisMode::cases(), 'value')));

            return Command::FAILURE;
        }

        try {
            $graphFormatOption = GraphFormat::from($input->getOption('graph-format'));
        } catch (ValueError $e) {
            $io->error('Invalid graph format. Allowed values are: ' . implode(',', array_column(GraphFormat::cases(), 'value')));

            return Command::FAILURE;
        }

        $outputPathOption = !empty($input->getOption('output')) ? (string) $input->getOption('output') : '';
        $graphOption = (bool) $input->getOption('graph');
        $entitiesOption = $input->getOption('entities');

        $io->text('Entities: ' . (empty($entitiesOption) ? 'all' : $entitiesOption));
        $io->text('Analysis mode: ' . $modeOption->value);
        $io->text('Graph generation: ' . ($graphOption ? 'yes' : 'no'));
        $io->text('Output path: ' . ($outputPathOption ?: 'not set'));

        if ($graphOption) {
            if (empty($outputPathOption)) {
                $io->error('Graph option requires setting output folder');

                return Command::FAILURE;
            } else {
                $outputPathOption = HelperService::cleanPath($outputPathOption);

                try {
                    // Ensure $outputPath exists, create it if it doesn't
                    if (!$this->filesystem->exists($outputPathOption)) {
                        $this->filesystem->mkdir($outputPathOption);
                    }
                } catch (IOExceptionInterface $e) {
                    $io->error("Can't create folder: " . $e->getMessage());

                    return Command::FAILURE;
                }
            }
        }

        $entitiesToAnalyze = $entitiesOption ? explode(',', $entitiesOption) : [];
        $relationships = $this->relationshipService->fetch($entitiesToAnalyze, $modeOption);
        if (empty($relationships)) {
            $io->error('No relationships detected');

            return Command::FAILURE;
        }

        $this->outputRelationships($relationships, $io, $modeOption);

        if ($graphOption) {
            if ($this->generateGraph($relationships, $outputPathOption, $modeOption, $graphFormatOption)) {
                $io->success("Graph image generated in: $outputPathOption");
            } else {
                $io->error("Can't save graph image");

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
    private function generateGraph(array $relationships, string $outputPath, AnalysisMode $mode, GraphFormat $format): bool
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

        $graphviz = new GraphViz();
        $graphviz->setFormat($format->value);
        $imageData = $graphviz->createImageData($graph);

        $fullPath = $outputPath . '/' . $mode->value . '.' . $format->value;
        try {
            $this->filesystem->dumpFile($fullPath, $imageData);
        } catch (IOExceptionInterface) {
            return false;
        }

        return true;
    }
}
