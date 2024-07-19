<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Tests;

use DoctrineRelationsAnalyserBundle\DoctrineRelationsAnalyserBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineRelationsAnalyserBundle(),
        ];
    }
}
