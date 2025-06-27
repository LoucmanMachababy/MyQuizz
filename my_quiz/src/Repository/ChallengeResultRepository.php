<?php

namespace App\Repository;

use App\Entity\ChallengeResult;
use App\Entity\Challenge;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChallengeResult>
 *
 * @method ChallengeResult|null find($id, $lockMode = null, $lockVersion = null)
 * @method ChallengeResult|null findOneBy(array $criteria, array $orderBy = null)
 * @method ChallengeResult[]    findAll()
 * @method ChallengeResult[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChallengeResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChallengeResult::class);
    }

    public function save(ChallengeResult $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ChallengeResult $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findResultsByChallenge(Challenge $challenge): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.challenge = :challenge')
            ->setParameter('challenge', $challenge)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.duration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findResultByUserAndChallenge(User $user, Challenge $challenge): ?ChallengeResult
    {
        return $this->findOneBy([
            'user' => $user,
            'challenge' => $challenge
        ]);
    }

    public function findResultsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedResultsByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.isCompleted = :isCompleted')
            ->setParameter('user', $user)
            ->setParameter('isCompleted', true)
            ->orderBy('r.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopResults(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isCompleted = :isCompleted')
            ->setParameter('isCompleted', true)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.duration', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getLeaderboardForChallenge(Challenge $challenge): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.challenge = :challenge')
            ->andWhere('r.isCompleted = :isCompleted')
            ->setParameter('challenge', $challenge)
            ->setParameter('isCompleted', true)
            ->orderBy('r.score', 'DESC')
            ->addOrderBy('r.duration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageScoreForChallenge(Challenge $challenge): float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.score) as avgScore')
            ->where('r.challenge = :challenge')
            ->andWhere('r.isCompleted = :isCompleted')
            ->setParameter('challenge', $challenge)
            ->setParameter('isCompleted', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 2) : 0.0;
    }

    public function getBestScoreForChallenge(Challenge $challenge): ?int
    {
        $result = $this->createQueryBuilder('r')
            ->select('MAX(r.score) as maxScore')
            ->where('r.challenge = :challenge')
            ->andWhere('r.isCompleted = :isCompleted')
            ->setParameter('challenge', $challenge)
            ->setParameter('isCompleted', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : null;
    }

    public function getFastestTimeForChallenge(Challenge $challenge): ?int
    {
        $result = $this->createQueryBuilder('r')
            ->select('MIN(r.duration) as minDuration')
            ->where('r.challenge = :challenge')
            ->andWhere('r.isCompleted = :isCompleted')
            ->andWhere('r.score > 0')
            ->setParameter('challenge', $challenge)
            ->setParameter('isCompleted', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : null;
    }
} 