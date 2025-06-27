<?php

namespace App\Repository;

use App\Entity\QuizHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizHistory>
 *
 * @method QuizHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuizHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuizHistory[]    findAll()
 * @method QuizHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuizHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizHistory::class);
    }

    public function save(QuizHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuizHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser($user)
    {
        return $this->createQueryBuilder('qh')
            ->andWhere('qh.user = :user')
            ->setParameter('user', $user)
            ->orderBy('qh.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics()
    {
        $qb = $this->createQueryBuilder('qh')
            ->select('COUNT(qh.id) as total_quizzes')
            ->addSelect('AVG(qh.score) as avg_score')
            ->addSelect('COUNT(DISTINCT qh.user) as unique_users');

        return $qb->getQuery()->getSingleResult();
    }

    public function getQuizzesByPeriod($period = '24h')
    {
        $date = new \DateTime();
        
        switch ($period) {
            case '24h':
                $date->modify('-24 hours');
                break;
            case 'week':
                $date->modify('-1 week');
                break;
            case 'month':
                $date->modify('-1 month');
                break;
            case 'year':
                $date->modify('-1 year');
                break;
        }

        return $this->createQueryBuilder('qh')
            ->andWhere('qh.completedAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
} 