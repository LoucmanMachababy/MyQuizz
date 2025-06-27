<?php

namespace App\Repository;

use App\Entity\Team;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Team>
 *
 * @method Team|null find($id, $lockMode = null, $lockVersion = null)
 * @method Team|null findOneBy(array $criteria, array $orderBy = null)
 * @method Team[]    findAll()
 * @method Team[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function save(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Team $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPublicTeams(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('t.points', 'DESC')
            ->addOrderBy('t.quizzesCompleted', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTeamsByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.members', 'm')
            ->where('t.owner = :user OR m = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTopTeams(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.isPublic = :isPublic')
            ->setParameter('isPublic', true)
            ->orderBy('t.points', 'DESC')
            ->addOrderBy('t.quizzesCompleted', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByInviteCode(string $inviteCode): ?Team
    {
        return $this->findOneBy(['inviteCode' => $inviteCode]);
    }

    public function findTeamsByCategory(string $category): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.teamQuizzes', 'tq')
            ->where('tq.category = :category')
            ->andWhere('t.isPublic = :isPublic')
            ->setParameter('category', $category)
            ->setParameter('isPublic', true)
            ->orderBy('t.points', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchTeams(string $search): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.name LIKE :search OR t.description LIKE :search')
            ->andWhere('t.isPublic = :isPublic')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('isPublic', true)
            ->orderBy('t.points', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 