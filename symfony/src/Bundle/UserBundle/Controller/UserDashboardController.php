<?php

namespace App\Bundle\UserBundle\Controller;

use App\Bundle\NewsBundle\Form\CommentType;
use App\Bundle\UserBundle\Form\UserDashboardType;
use App\Bundle\UserBundle\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserDashboardController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $security;
    private $passwordEncoder;
    private $validator;
    private $userAuthenticator;
    private $userProvider;
    private $user;
    private $comment;
    private $likeComment;

    /**
     * Class constructor
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $knpPaginator
     * @param Security $security
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param ValidatorInterface $validator
     * @param UserAuthenticator $userAuthenticator
     * @param UserProviderInterface $userProvider
     */
    public function __construct(EntityManagerInterface $entityManager,
                                PaginatorInterface $knpPaginator,
                                Security $security,
                                UserPasswordEncoderInterface $passwordEncoder,
                                ValidatorInterface $validator,
                                UserAuthenticator $userAuthenticator,
                                UserProviderInterface $userProvider)
    {
        $this->entityManager = $entityManager;
        $this->knpPaginator = $knpPaginator;
        $this->security = $security;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->userAuthenticator = $userAuthenticator;
        $this->userProvider = $userProvider;
        $this->user = $this->entityManager->getRepository('UserBundle:User');
        $this->comment = $this->entityManager->getRepository('NewsBundle:Comment');
        $this->likeComment = $this->entityManager->getRepository('NewsBundle:LikeComment');
    }

    /**
     * @Route("/user_dashboard", name="user_dashboard")
     * @param Request $request
     * @return Response
     */
    public function getUserDashboardInfo(Request $request)
    {
        $authorizedUser = $this->security->getUser();

        if (isset($authorizedUser)) {
            $commentByUserId = $this->comment->getCommentByUserId($authorizedUser->getId());
            $userCommentList = [];
            $commentStatus = $request->request->get('commentStatus');

            if (isset($commentStatus)) {
                $commentId = $request->request->get('commentId');
                $commentText = trim($request->request->get('commentText'));
                $comment = $this->comment->findById($commentId);

                if (count($comment) > 0) {
                    $comment = $comment[0];
                    $commentForm = $this->createForm(CommentType::class, $comment);

                    $entityManager = $this->getDoctrine()->getManager();
                    $commentData = $commentForm->getData();

                    if ($commentStatus === 'Save') {
                        $commentData->setText($commentText);

                        $entityManager->persist($commentData);
                        $entityManager->flush();

                        $this->addFlash('changed', 'Comment changed successfully');

                    } else if ($commentStatus === 'Delete') {
                        $commentId = $commentData->getId();
                        $allLikeComment = $this->likeComment->findLikeCommentByCommentId($commentId);

                        foreach ($allLikeComment as $likeComment) {
                            $entityManager->remove($likeComment);
                        }

                        $entityManager->remove($commentData);
                        $entityManager->flush();

                        $this->addFlash('deleted', 'Comment deleted successfully');
                    }
                }
            }

            foreach ($commentByUserId as $commentId => $comment) {
                $commentId = $comment->getId();

                $comment = $this->comment->findById($commentId);

                if (count($comment) > 0) {
                    $comment = $comment[0];

                    $commentId = $comment->getId();
                    $commentText = trim($comment->getText());
                    $commentCreated = $comment->getCreated();
                    $commentNewsTitle = $comment->getNews()->getTitle();
                    $commentNewsId = $comment->getNews()->getId();

                    $userCommentList[$commentId] = [
                        'newsId' => $commentNewsId,
                        'newsTitle' => $commentNewsTitle,
                        'commentId' => $commentId,
                        'commentText' => $commentText,
                        'commentCreated' => $commentCreated,
                    ];
                }
            }

            $form = $this->createForm(UserDashboardType::class, $authorizedUser);
            $form->add('save', SubmitType::class);
            $form->handleRequest($request);
            $errorEmailValidation = false;
            $emailConstraint = new Assert\Email();

            if ($form->isSubmitted() && $form->isValid()) {
                $userInfoUpdate = $form->getData();
                $email = $userInfoUpdate->getEmail();

                $NewPassword = $request->request->get('NewPassword');
                $ConfirmPassword = $request->request->get('ConfirmPassword');

                $errorsValidate = $this->validator->validate(
                    $email,
                    $emailConstraint
                );

                if ($NewPassword !== '' && $ConfirmPassword !== '') {
                    if ($NewPassword === $ConfirmPassword) {
                        $userInfoUpdate->setPassword($this->passwordEncoder->encodePassword(
                            $userInfoUpdate,
                            $NewPassword
                        ));

                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($userInfoUpdate);
                        $entityManager->flush();

                        $this->addFlash('successful', 'User information successfully updated');

                    } else {
                        $this->addFlash('danger', 'Passwords do not match');
                    }

                } else {
                    if (0 === count($errorsValidate)) {
                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($userInfoUpdate);
                        $entityManager->flush();

                        $this->addFlash('successful', 'User information successfully updated');

                    } else {
                        $errorEmailValidation = true;
                    }
                }

            } else if (count($form->getErrors()) > 0) {
                $errorsForm = $form->getErrors();
                $errorMessage = $errorsForm[0]->getMessage();

                $this->addFlash('danger', $errorMessage);
            }

            usort($userCommentList, function($a, $b) {
                if ($a['commentCreated'] == $b['commentCreated']) {
                    return 0;
                }

                return ($a['commentCreated'] > $b['commentCreated']) ? -1 : 1;
            });

            $paginationUserCommentList = $this->knpPaginator->paginate(
                $userCommentList,
                $request->query->getInt('page', 1),
                3
            );

            return $this->render('user/userDashboard.html.twig', [
                'registrationForm' => $form->createView(),
                'error' => $errorEmailValidation,
                'comments' => $paginationUserCommentList,
            ]);

        }

        $this->addFlash('successful', 'Please log in for show User Dashboard');

        return $this->redirectToRoute('app_login');
    }
}
