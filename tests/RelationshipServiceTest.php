<?php

declare(strict_types=1);

namespace DoctrineRelationsAnalyserBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use DoctrineRelationsAnalyserBundle\Enum\AnalysisMode;
use DoctrineRelationsAnalyserBundle\Enum\DeletionType;
use DoctrineRelationsAnalyserBundle\Service\RelationshipService;
use DoctrineRelationsAnalyserBundle\Tests\Entity\Comment;
use DoctrineRelationsAnalyserBundle\Tests\Entity\Post;
use DoctrineRelationsAnalyserBundle\Tests\Entity\Tag;
use DoctrineRelationsAnalyserBundle\Tests\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \RelationshipService
 */
final class RelationshipServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private RelationshipService $relationshipService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        // @phpstan-ignore-next-line
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $this->relationshipService = new RelationshipService($this->entityManager);
    }

    public function testFetch(): void
    {
        $metadataAll = $this->relationshipService->fetch([], AnalysisMode::ALL);

        $keys = array_keys($metadataAll);
        $this->assertContains(Post::class, $keys);
        $this->assertContains(Tag::class, $keys);
        $this->assertContains(Comment::class, $keys);
        $this->assertContains(User::class, $keys);

        $metadataDeletions = $this->relationshipService->fetch([], AnalysisMode::DELETIONS);
        $this->assertCount(2, $metadataDeletions[Post::class][Comment::class]['deletions']);
        $this->assertEquals(DeletionType::ORPHAN_REMOVAL, $metadataDeletions[Post::class][Comment::class]['deletions'][0]['type']);
    }
}
