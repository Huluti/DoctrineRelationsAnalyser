<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Tests;

use Doctrine\ORM\Mapping\ManyToManyAssociationMapping;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use Doctrine\ORM\Mapping\OneToOneAssociationMapping;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \HelperService
 */
final class HelperServiceTest extends KernelTestCase
{
    public function testGetRelationType(): void
    {
        $this->assertSame(HelperService::getRelationType(OneToOneAssociationMapping::class), 'OneToOne');
        $this->assertSame(HelperService::getRelationType(ManyToOneAssociationMapping::class), 'ManyToOne');
        $this->assertSame(HelperService::getRelationType(OneToManyAssociationMapping::class), 'OneToMany');
        $this->assertSame(HelperService::getRelationType(ManyToManyAssociationMapping::class), 'ManyToMany');
    }

    public function testGetCleanPath(): void
    {
        $this->assertSame(HelperService::cleanPath('data '), 'data');
        $this->assertSame(HelperService::cleanPath(' data/'), 'data');
        $this->assertSame(HelperService::cleanPath('data'), 'data');
    }
}
