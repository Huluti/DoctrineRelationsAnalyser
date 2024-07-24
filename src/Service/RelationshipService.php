<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
use DoctrineRelationsAnalyserBundle\Enum\DeletionType;
use DoctrineRelationsAnalyserBundle\Enum\Level;

class RelationshipService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string> $entities
     *
     * @return array<mixed>
     */
    public function fetch(array $entities, AnalysisMode $mode): array
    {
        $restrictedEntities = !empty($entities);
        $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $relationships = [];

        foreach ($metaData as $meta) {
            $className = $meta->getName();
            if ($restrictedEntities && !in_array($className, $entities, true)) {
                continue; // Skip entities not in the list
            }

            $relationships[$className] = [];
            foreach ($meta->associationMappings as $fieldName => $association) {
                // @phpstan-ignore-next-line
                $targetEntity = is_object($association) ? $association->targetEntity : $association['targetEntity'];

                $relationDetails = [
                    'field' => $fieldName,
                    'type' => $association['type'],
                ];

                if (AnalysisMode::DELETIONS === $mode) {
                    $deletions = [];

                    // @phpstan-ignore-next-line
                    $orphanRemoval = is_object($association) ? $association->orphanRemoval : $association['orphanRemoval'];
                    if ($orphanRemoval) {
                        $deletions[] = [
                            'type' => DeletionType::ORPHAN_REMOVAL,
                            'level' => Level::ORM,
                            'value' => 'true',
                        ];
                    }

                    // @phpstan-ignore-next-line
                    $cascade = is_object($association) ? $association->cascade : $association['cascade'];
                    if ($cascade && in_array('remove', $cascade, true)) {
                        $deletions[] = [
                            'type' => DeletionType::CASCADE,
                            'level' => Level::ORM,
                            'value' => '["remove"]',
                        ];
                    }

                    // @phpstan-ignore-next-line
                    if (is_object($association) && property_exists($association, 'joinColumns')) {
                        $joinColumns = $association->joinColumns;
                    } elseif (is_array($association) && array_key_exists('joinColumns', $association)) { // @phpstan-ignore-line
                        $joinColumns = $association['joinColumns'];
                    } else {
                        $joinColumns = [];
                    }
                    if (!empty($joinColumns)) {
                        // @phpstan-ignore-next-line
                        $onDelete = is_object($joinColumns[0]) ? $joinColumns[0]->onDelete : $joinColumns[0]['onDelete'];
                        if (!empty($onDelete)) {
                            $deletions[] = [
                                'type' => DeletionType::ON_DELETE,
                                'level' => Level::DATABASE,
                                'value' => $onDelete,
                            ];
                        }
                    }

                    $relationDetails['deletions'] = $deletions;
                }

                $relationships[$className][$targetEntity] = $relationDetails;
            }
        }

        return $relationships;
    }
}
