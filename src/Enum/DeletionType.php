<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Enum;

enum DeletionType: string
{
    case ORPHAN_REMOVAL = 'orphanRemoval';
    case CASCADE = 'cascade';
    case ON_DELETE = 'onDelete';
}
