<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Tests;

use Doctrine\ORM\Mapping\ClassMetadata;
use DoctrineRelationsAnalyserBundle\Service\HelperService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers HelperService
 */
final class HelperServiceTest extends KernelTestCase
{
    public function testGetRelationType(): void
    {
        $this->assertSame(HelperService::getRelationType(ClassMetadata::ONE_TO_ONE), 'OneToOne');
        $this->assertSame(HelperService::getRelationType(ClassMetadata::MANY_TO_ONE), 'ManyToOne');
        $this->assertSame(HelperService::getRelationType(ClassMetadata::ONE_TO_MANY), 'OneToMany');
        $this->assertSame(HelperService::getRelationType(ClassMetadata::MANY_TO_MANY), 'ManyToMany');
    }
}
