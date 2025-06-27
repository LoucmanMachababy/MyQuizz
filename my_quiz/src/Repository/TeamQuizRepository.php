<?php

namespace App\Repository;

use App\Entity\TeamQuiz;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamQuiz>
 *
 * @method TeamQuiz|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamQuiz|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamQuiz[]    findAll()
 * @method TeamQuiz[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamQuizRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamQuiz::class);
    }

    public function save(TeamQuiz $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TeamQuiz $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveQuizzesByTeam(Team $team): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.team = :team')
            ->andWhere('tq.isActive = :isActive')
            ->setParameter('team', $team)
            ->setParameter('isActive', true)
            ->orderBy('tq.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedQuizzesByTeam(Team $team): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.team = :team')
            ->andWhere('tq.isCompleted = :isCompleted')
            ->setParameter('team', $team)
            ->setParameter('isCompleted', true)
            ->orderBy('tq.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findQuizzesByUser(User $user): array
    {
        return $this->createQueryBuilder('tq')
            ->join('tq.participants', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('tq.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopQuizzes(int $limit = 10): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.isCompleted = :isCompleted')
            ->setParameter('isCompleted', true)
            ->orderBy('tq.score', 'DESC')
            ->addOrderBy('tq.correctAnswers', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findQuizzesByCategory(string $category): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.category = :category')
            ->andWhere('tq.isCompleted = :isCompleted')
            ->setParameter('category', $category)
            ->setParameter('isCompleted', true)
            ->orderBy('tq.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentQuizzes(int $limit = 20): array
    {
        return $this->createQueryBuilder('tq')
            ->where('tq.isCompleted = :isCompleted')
            ->setParameter('isCompleted', true)
            ->orderBy('tq.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 