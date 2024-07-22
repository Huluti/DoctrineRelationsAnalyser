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
                $relationDetails = [
                    'field' => $fieldName,
                    'targetEntity' => $association->targetEntity,
                    'type' => get_class($association),
                ];

                if (AnalysisMode::DELETIONS === $mode) {
                    $deletions = [];

                    if (isset($association->orphanRemoval) && $association->orphanRemoval) {
                        $deletions[] = [
                            'type' => DeletionType::ORPHAN_REMOVAL,
                            'level' => Level::ORM,
                            'value' => 'true',
                        ];
                    }

                    if (isset($association->cascade) && in_array('remove', $association->cascade, true)) {
                        $deletions[] = [
                            'type' => DeletionType::CASCADE,
                            'level' => Level::ORM,
                            'value' => '["remove"]',
                        ];
                    }

                    if (!empty($association->joinColumns)) {
                        if (!empty($association->joinColumns[0]->onDelete)) {
                            $deletions[] = [
                                'type' => DeletionType::ON_DELETE,
                                'level' => Level::DATABASE,
                                'value' => $association->joinColumns[0]->onDelete,
                            ];
                        }
                    }

                    $relationDetails['deletions'] = $deletions;
                }

                $relationships[$className][] = $relationDetails;
            }
        }

        return $relationships;
    }
}
