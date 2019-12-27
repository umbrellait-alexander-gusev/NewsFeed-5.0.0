<?php

namespace App\Bundle\NewsBundle\Repository;

use App\Bundle\NewsBundle\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function findActive()
    {
        return $this
            ->createQueryBuilder('news')
            ->where('news.active = :active')
            ->setParameter('active', 1)
            ->getQuery()
            ->getResult();
    }

    public function getCountNewsByCategory($categoryId)
    {
        if (is_null($categoryId)) {
            return $this
                ->createQueryBuilder('news')
                ->where('news.category is NULL')
                ->getQuery()
                ->getResult();
        }

        return $this
            ->createQueryBuilder('news')
            ->where('news.category = :category')
            ->setParameter('category', $categoryId)
            ->getQuery()
            ->getResult();
    }
}
