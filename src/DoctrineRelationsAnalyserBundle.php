<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineRelationsAnalyserBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}