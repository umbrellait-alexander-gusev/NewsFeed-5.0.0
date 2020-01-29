<?php

namespace App\Bundle\EmailBundle\Service;

use Firebase\JWT\JWT;
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
     * @param $jwtToken
     * @param $key
     * @param $hostName
     * @return JsonResponse
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function sendSuccessfulRegistration($jwtToken, $key, $hostName)
    {
        $decoded = JWT::decode($jwtToken, $key, array('HS256'));
        $decodedArr = (array)$decoded;

        $htmlContents = $this->twig->render('email/successfulRegistration.html.twig', [
                'userId' => $decodedArr['userId'],
                'userName' => $decodedArr['userName'],
                'hostName' => $hostName,
                'userRegistrationToken' => $jwtToken,
            ]
        );

        $message = (new \Swift_Message('Successful registration on News feed'))
            ->setFrom('sgv89@mail.ru')
            ->setTo($decodedArr['userEmail'])
            ->setBody(
                $htmlContents,
                'text/html'
            );

        $this->mailer->send($message);

        return new JsonResponse(['Successful Mail']);
    }
}
