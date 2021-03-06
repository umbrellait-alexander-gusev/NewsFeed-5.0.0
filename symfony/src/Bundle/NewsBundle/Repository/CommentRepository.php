<?php

namespace App\Bundle\NewsBundle\Repository;

use App\Bundle\NewsBundle\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function getCommentsByNewsId($newsId)
    {
        return $this
            ->createQueryBuilder('comment')
            ->where('comment.news = :news')
            ->setParameter('news', $newsId)
            ->getQuery()
            ->getResult();
    }

    public function getCommentByUserId($userId)
    {
        return $this
            ->createQueryBuilder('comment')
            ->where('comment.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();
    }
}
