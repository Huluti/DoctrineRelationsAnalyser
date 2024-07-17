<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
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
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'Output path for reports generated')
            ->addOption('graph', null, InputOption::VALUE_NONE, 'Generate Graphviz graph')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start = microtime(true);

        $io = new SymfonyStyle($input, $output);

        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $relationships = [];

        foreach ($metaData as $meta) {
            $className = $meta->getName();
            foreach ($meta->associationMappings as $fieldName => $association) {
                // if (isset($association['onDelete']) || isset($association['orphanRemoval']) || (isset($association['cascade']) && in_array('remove', $association['cascade']))) {
                $relationDetails = [
                    'field' => $fieldName,
                    'targetEntity' => $association['targetEntity'],
                    'type' => $association['type'],
                    // 'onDelete' => $association['onDelete'] ?? null,
                    // 'orphanRemoval' => $association['orphanRemoval'] ?? null,
                    // 'cascade' => $association['cascade'] ?? null,
                ];
                $relationships[$className][] = $relationDetails;
                // }
            }
        }

        $this->outputRelationships($relationships, $io);

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
                if ($this->generateGraph($relationships, $outputPath)) {
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

    private function outputRelationships(array $relationships, SymfonyStyle $io): void
    {
        foreach ($relationships as $entity => $relations) {
            $io->section("Entity: $entity");
            foreach ($relations as $relation) {
                $io->text("Field: {$relation['field']}");
                $io->text("Target Entity: {$relation['targetEntity']}");
                $io->text('Type: ' . $this->getRelationType($relation['type']));
                // if ($relation['onDelete']) {
                //     $io->text("On Delete: {$relation['onDelete']}");
                // }
                // if ($relation['orphanRemoval']) {
                //     $io->text("Orphan Removal: " . ($relation['orphanRemoval'] ? 'true' : 'false'));
                // }
                // if ($relation['cascade']) {
                //     $io->text("Cascade: " . implode(', ', $relation['cascade']));
                // }
                $io->newLine();
            }
        }
    }

    private function generateGraph(array $relationships, string $outputPath): bool
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
                    $edge = $graph->createEdgeDirected($nodes[$entity], $nodes[$targetEntity]);
                    $edge->setAttribute('graphviz.label', $this->getRelationType($relation['type']));
                }
            }
        }

        $graphviz = new GraphViz();
        $graphviz->setFormat('png');

        $imageData = $graphviz->createImageData($graph);

        $fullPath = $outputPath . '/graph.png';

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
