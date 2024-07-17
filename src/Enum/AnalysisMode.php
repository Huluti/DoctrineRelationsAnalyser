<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Enum;

enum AnalysisMode: string
{
    case ALL = 'all';
    case DELETIONS = 'deletions';
}
