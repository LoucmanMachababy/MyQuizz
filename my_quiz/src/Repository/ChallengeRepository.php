<?php

namespace App\Repository;

use App\Entity\Challenge;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 *
 * @method Challenge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Challenge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Challenge[]    findAll()
 * @method Challenge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    public function save(Challenge $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Challenge $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveChallengesByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.challenger = :user OR c.challenged = :user')
            ->andWhere('c.isActive = :isActive')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingChallengesByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.challenged = :user')
            ->andWhere('c.isActive = :isActive')
            ->andWhere('c.isAccepted = :isAccepted')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->setParameter('isAccepted', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedChallengesByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.challenger = :user OR c.challenged = :user')
            ->andWhere('c.isCompleted = :isCompleted')
            ->setParameter('user', $user)
            ->setParameter('isCompleted', true)
            ->orderBy('c.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findExpiredChallenges(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->andWhere('c.expiresAt <= :now')
            ->setParameter('isActive', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function findRecentChallenges(int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isCompleted = :isCompleted')
            ->setParameter('isCompleted', true)
            ->orderBy('c.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findChallengesByCategory(string $category): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.category = :category')
            ->andWhere('c.isCompleted = :isCompleted')
            ->setParameter('category', $category)
            ->setParameter('isCompleted', true)
            ->orderBy('c.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getChallengeStats(User $user): array
    {
        $qb = $this->createQueryBuilder('c');
        
        $stats = $qb->select('COUNT(c.id) as total')
            ->addSelect('SUM(CASE WHEN c.isCompleted = :completed THEN 1 ELSE 0 END) as completed')
            ->addSelect('SUM(CASE WHEN c.isAccepted = :accepted THEN 1 ELSE 0 END) as accepted')
            ->where('c.challenger = :user OR c.challenged = :user')
            ->setParameter('user', $user)
            ->setParameter('completed', true)
            ->setParameter('accepted', true)
            ->getQuery()
            ->getSingleResult();

        return [
            'total' => (int) $stats['total'],
            'completed' => (int) $stats['completed'],
            'accepted' => (int) $stats['accepted'],
            'pending' => (int) $stats['total'] - (int) $stats['accepted']
        ];
    }
} 