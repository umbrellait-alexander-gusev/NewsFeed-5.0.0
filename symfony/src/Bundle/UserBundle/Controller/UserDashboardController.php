<?php

namespace App\Bundle\UserBundle\Controller;

use App\Bundle\NewsBundle\Form\CommentType;
use App\Bundle\UserBundle\Form\UserInformationType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class UserDashboardController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $security;
    private $comment;
    private $likeComment;

    /**
     * Class constructor
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $knpPaginator
     * @param Security $security
     */
    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $knpPaginator, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->knpPaginator = $knpPaginator;
        $this->security = $security;
        $this->comment = $this->entityManager->getRepository('NewsBundle:comment');
        $this->likeComment = $this->entityManager->getRepository('NewsBundle:LikeComment');
    }

    /**
     * @Route("/user_dashboard", name="user_dashboard")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getUserDashboardInfo(Request $request)
    {
        $authorizedUser = $this->security->getUser();
        $likeCommentByUserId = $this->likeComment->findLikeCommentByUserId($authorizedUser);
        $userCommentList = [];

        foreach ($likeCommentByUserId as $likeCommentId => $likeComment) {
            $commentId = $likeComment->getComment()->getId();

            $comment = $this->comment->findById($commentId)[0];

            $commentText = $comment->getText();
            $commentNewsTitle = $comment->getNews()->getTitle();
            $commentNewsId = $comment->getNews()->getId();

            $userCommentList[$likeCommentId] = [
                'newsId' => $commentNewsId,
                'newsTitle' => $commentNewsTitle,
                'commentText' => $commentText,
            ];
        }

        $form = $this->createForm(UserInformationType::class, $authorizedUser);
        $form->add('save', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRegistration = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($userRegistration);
            $entityManager->flush();

            $this->addFlash('successful', 'Your data has been updated successfully');
        }

        $paginationUserCommentList = $this->knpPaginator->paginate(
            $userCommentList,
            $request->query->getInt('page', 1),
            3
        );

        return $this->render('user/userDashboard.html.twig', [
            'registrationForm' => $form->createView(),
            'comments' => $paginationUserCommentList,
        ]);
    }
}
