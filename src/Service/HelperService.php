<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;

final class HelperService
{
    public static function getRelationType(int $type): string
    {
        return match ($type) {
            ClassMetadata::ONE_TO_ONE => 'OneToOne',
            ClassMetadata::MANY_TO_ONE => 'ManyToOne',
            ClassMetadata::ONE_TO_MANY => 'OneToMany',
            ClassMetadata::MANY_TO_MANY => 'ManyToMany',
            default => 'Unknown',
        };
    }
}
