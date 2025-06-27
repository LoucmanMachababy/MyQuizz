<?php

namespace App\Repository;

use App\Entity\TeamQuizParticipant;
use App\Entity\TeamQuiz;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamQuizParticipant>
 *
 * @method TeamQuizParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamQuizParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamQuizParticipant[]    findAll()
 * @method TeamQuizParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamQuizParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamQuizParticipant::class);
    }

    public function save(TeamQuizParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TeamQuizParticipant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findParticipantsByQuiz(TeamQuiz $quiz): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.teamQuiz = :quiz')
            ->setParameter('quiz', $quiz)
            ->orderBy('p.score', 'DESC')
            ->addOrderBy('p.duration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findParticipantByUserAndQuiz(User $user, TeamQuiz $quiz): ?TeamQuizParticipant
    {
        return $this->findOneBy([
            'user' => $user,
            'teamQuiz' => $quiz
        ]);
    }

    public function findTopParticipants(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isCompleted = :isCompleted')
            ->setParameter('isCompleted', true)
            ->orderBy('p.score', 'DESC')
            ->addOrderBy('p.duration', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findParticipantsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.startedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCompletedParticipantsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.isCompleted = :isCompleted')
            ->setParameter('user', $user)
            ->setParameter('isCompleted', true)
            ->orderBy('p.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getLeaderboardForQuiz(TeamQuiz $quiz): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.teamQuiz = :quiz')
            ->andWhere('p.isCompleted = :isCompleted')
            ->setParameter('quiz', $quiz)
            ->setParameter('isCompleted', true)
            ->orderBy('p.score', 'DESC')
            ->addOrderBy('p.duration', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getAverageScoreForQuiz(TeamQuiz $quiz): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('AVG(p.score) as avgScore')
            ->where('p.teamQuiz = :quiz')
            ->andWhere('p.isCompleted = :isCompleted')
            ->setParameter('quiz', $quiz)
            ->setParameter('isCompleted', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 2) : 0.0;
    }
} 