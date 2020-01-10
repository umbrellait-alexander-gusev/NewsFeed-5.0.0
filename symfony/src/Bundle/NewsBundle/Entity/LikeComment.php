<?php

namespace App\Bundle\NewsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Bundle\NewsBundle\Repository\LikeCommentRepository")
 */
class LikeComment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Bundle\UserBundle\Entity\User")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Bundle\NewsBundle\Entity\Comment")
     */
    private $comment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $likeComment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getLikeComment(): ?bool
    {
        return $this->likeComment;
    }

    public function setLikeComment(?bool $likeComment): self
    {
        $this->likeComment = $likeComment;

        return $this;
    }
}
