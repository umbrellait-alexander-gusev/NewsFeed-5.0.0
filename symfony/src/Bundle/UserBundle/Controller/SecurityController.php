<?php

namespace App\Bundle\UserBundle\Controller;

use App\Bundle\UserBundle\Form\RegistrationType;
use App\Bundle\UserBundle\Security\UserAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    private $entityManager;
    private $passwordEncoder;
    private $validator;
    private $userAuthenticator;
    private $userProvider;
    private $user;

    /**
     * Class constructor
     * @param EntityManagerInterface $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param ValidatorInterface $validator
     * @param UserAuthenticator $userAuthenticator
     * @param UserProviderInterface $userProvider
     */
    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder, ValidatorInterface $validator, UserAuthenticator $userAuthenticator, UserProviderInterface $userProvider)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->userAuthenticator = $userAuthenticator;
        $this->userProvider = $userProvider;
        $this->user = $this->entityManager->getRepository('UserBundle:User');
    }

    /**
     * @Route("/login", name="app_login")
     * @param AuthenticationUtils $authenticationUtils
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
                'last_username' => $lastUsername,
                'error' => $error
            ]
        );
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/registration", name="app_registration")
     * @param Request $request
     * @return Response
     */
    public function registration(Request $request)
    {
        $form = $this->createForm(RegistrationType::class);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);
        $errorEmailValidation = false;

        $emailConstraint = new Assert\Email();

        if ($form->isSubmitted() && $form->isValid()) {
            $userRegistration = $form->getData();
            $email = $userRegistration->getEmail();
            $password = $userRegistration->getPassword();
            $getUserByEmail = $this->user->getUserByEmail($email);

            $errorsValidate = $this->validator->validate(
                $email,
                $emailConstraint
            );

            $userRegistration->setPassword($this->passwordEncoder->encodePassword(
                $userRegistration,
                $password
            ));

            if (0 === count($errorsValidate)) {
                if (count($getUserByEmail) > 0) {
                    $this->addFlash('danger', 'A user with this mail already exists');

                } else {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($userRegistration);
                    $entityManager->flush();

                    $this->addFlash('successful', 'Registration was successful, please log in');

                    return $this->redirectToRoute('app_login');
                }

            } else {
                $errorEmailValidation = true;
            }

        } else if (count($form->getErrors()) > 0) {
            $errorsForm = $form->getErrors();
            $errorMessage = $errorsForm[0]->getMessage();

            $this->addFlash('danger', $errorMessage);
        }

        return $this->render('security/registration.html.twig', [
                'registrationForm' => $form->createView(),
                'error' => $errorEmailValidation,
            ]
        );
    }
}
