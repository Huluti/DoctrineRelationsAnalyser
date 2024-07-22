<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Service;

use Doctrine\ORM\Mapping\ManyToManyAssociationMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\OneToOneAssociationMapping;

final class HelperService
{
    public static function getRelationType(string $type): string
    {
        return match ($type) {
            OneToOneAssociationMapping::class => 'OneToOne',
            ManyToOneAssociationMapping::class => 'ManyToOne',
            OneToManyAssociationMapping::class => 'OneToMany',
            ManyToManyAssociationMapping::class => 'ManyToMany',
            default => 'Unknown',
        };
    }

    public static function cleanPath(string $path): string
    {
        return rtrim(trim($path), '/');
    }
}
