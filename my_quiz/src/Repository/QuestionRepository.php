<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Trouve toutes les catégories disponibles
     */
    public function findAllCategories(): array
    {
        $qb = $this->createQueryBuilder('q')
            ->select('DISTINCT c.nom')
            ->join('q.categorie', 'c')
            ->orderBy('c.nom', 'ASC');

        $result = $qb->getQuery()->getResult();
        return array_column($result, 'nom');
    }

    /**
     * Trouve des questions par catégorie et difficulté
     */
    public function findByCategoryAndDifficulty(string $category, string $difficulty, int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->join('q.categorie', 'c')
            ->andWhere('c.nom = :category')
            ->andWhere('q.difficulty = :difficulty')
            ->setParameter('category', $category)
            ->setParameter('difficulty', $difficulty)
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des questions par catégorie
     */
    public function findByCategory(string $category, int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->join('q.categorie', 'c')
            ->andWhere('c.nom = :category')
            ->setParameter('category', $category)
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve des questions aléatoires
     */
    public function findRandomQuestions(int $limit = 10): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Question[] Returns an array of Question objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('q.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Question
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
