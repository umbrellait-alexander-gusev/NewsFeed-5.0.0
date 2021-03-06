<?php

namespace App\Bundle\NewsBundle\Repository;

use App\Bundle\NewsBundle\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findCategoryById($categoryId)
    {
        return $this
            ->createQueryBuilder('category')
            ->where('category.id = :id')
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->getResult();
    }
}
