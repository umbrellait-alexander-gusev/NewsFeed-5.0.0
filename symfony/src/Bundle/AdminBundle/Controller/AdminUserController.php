<?php

namespace App\Bundle\AdminBundle\Controller;

use App\Bundle\UserBundle\Form\UserDashboardType;
use App\Bundle\UserBundle\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/admin")
 */
class AdminUserController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $security;
    private $passwordEncoder;
    private $validator;
    private $userAuthenticator;
    private $userProvider;
    private $user;

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
    }

    /**
     * Get user list
     *
     * @Route("/user_list", name="admin_user_list")
     * @param Request $request
     * @return Response
     */
    public function getUserList(Request $request)
    {
        $userList = $this->user->findAll();
        $userFilterList = [];

        foreach ($userList as $userKey => $user) {
            $userFilterList[$userKey] = [
                'userId' => $user->getId(),
                'userEmail' => $user->getEmail(),
            ];

            $userFirstName = $user->getFirstName();
            $userLastName = $user->getLastName();
            $userFullName = $userFirstName . ' ' . $userLastName;

            $userFilterList[$userKey] = array_merge($userFilterList[$userKey], array("userName"=> $userFullName));
            if (is_null($userFullName) || $userFullName === ' ') {
                $userFilterList[$userKey] = array_merge($userFilterList[$userKey], array("userName"=>"No name"));
            }

            $userRoles = $user->getRoles();
            $flipUserRole = array_flip($userRoles);

            $userFilterList[$userKey] = array_merge($userFilterList[$userKey], array("userRole"=>"User"));
            if (array_key_exists('ROLE_ADMIN', $flipUserRole)) {
                $userFilterList[$userKey] = array_merge($userFilterList[$userKey], array("userRole"=>"Admin"));
            }
        }

        $userPagination = $this->knpPaginator->paginate(
            $userFilterList,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/user/userList.html.twig', [
            'userList' => $userPagination,
        ]);
    }

    /**
     * Edit user
     *
     * @Route("/edit_user/{id}", name="admin_edit_user")
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function editUser(Request $request, $id)
    {
        $user = $this->user->findOneBy(['id' => $id]);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $userOldEmail = $user->getEmail();

        $form = $this->createForm(UserDashboardType::class, $user);
        $form->add('save', SubmitType::class);
        $form->handleRequest($request);

        $errorEmailValidation = false;
        $emailConstraint = new Assert\Email();

        if ($form->isSubmitted() && $form->isValid()) {
            $userInfoUpdate = $form->getData();
            $userNewEmail = $userInfoUpdate->getEmail();

            $NewPassword = $request->request->get('NewPassword');
            $ConfirmPassword = $request->request->get('ConfirmPassword');

            $errorsValidate = $this->validator->validate(
                $userNewEmail,
                $emailConstraint
            );

            if ($userOldEmail !== $userNewEmail) {
                $this->addFlash('danger', 'This email is already taken');

                return $this->render('admin/user/editUser.html.twig', [
                    'userForm' => $form->createView(),
                    'error' => $errorEmailValidation,
                ]);
            }

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

        return $this->render('admin/user/editUser.html.twig', [
            'userForm' => $form->createView(),
            'error' => $errorEmailValidation,
        ]);
    }

    /**
     * Delete user
     *
     * @Route("/delete_user/{id}", name="admin_delete_user")
     * @param $id
     * @return RedirectResponse
     */
    public function deleteNews($id)
    {
        $userById = $this->user->find($id);

        if (!$userById) {
            throw $this->createNotFoundException('News not found');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($userById);
        $entityManager->flush();

        $this->addFlash('danger', 'User successfully deleted');
        return $this->redirectToRoute('admin_user_list');
    }
}
