<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use DoctrineRelationsAnalyserBundle\DoctrineRelationsAnalyserBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DoctrineRelationsAnalyserBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/config.yaml');
    }

    public function getCacheDir(): string
    {
        return 'var/cache';
    }

    public function getLogDir(): string
    {
        return 'var/log';
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }
}
