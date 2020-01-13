<?php

namespace App\Bundle\NewsBundle\Repository;

use App\Bundle\NewsBundle\Entity\LikeComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LikeComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method LikeComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method LikeComment[]    findAll()
 * @method LikeComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LikeCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LikeComment::class);
    }

    public function findLikeCommentByUserId($userId)
    {
        return $this
            ->createQueryBuilder('like_comment')
            ->where('like_comment.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findLikeCommentByCommentId($commentId)
    {
        return $this
            ->createQueryBuilder('like_comment')
            ->where('like_comment.comment = :comment')
            ->setParameter('comment', $commentId)
            ->getQuery()
            ->getResult();
    }
}
