<?php

namespace App\Bundle\EmailBundle\Service;

use Swift_Mailer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\{Environment, Error\LoaderError, Error\RuntimeError, Error\SyntaxError};

/**
 * Service for sending letter
 */
class EmailService
{
    private $mailer;
    private $twig;

    /**
     * Constructor of class
     * @param Swift_Mailer $mailer
     * @param Environment $twig
     */
    public function __construct(Swift_Mailer $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * Send notification for User: Successful registration
     * @param $userId
     * @param $userName
     * @param $userEmail
     * @param $hostName
     * @param $userRegistrationToken
     * @return JsonResponse
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function sendSuccessfulRegistration($userId, $userName, $userEmail, $hostName, $userRegistrationToken)
    {
        $htmlContents = $this->twig->render('email/successfulRegistration.html.twig', [
                'userId' => $userId,
                'userName' => $userName,
                'hostName' => $hostName,
                'userRegistrationToken' => $userRegistrationToken,
            ]
        );

        $message = (new \Swift_Message('Successful registration on News feed'))
            ->setFrom('sgv89@mail.ru')
            ->setTo($userEmail)
            ->setBody(
                $htmlContents,
                'text/html'
            );

        $this->mailer->send($message);

        return new JsonResponse(['Successful Mail']);
    }
}
