<?php

namespace App\Bundle\LexikJWTAuthenticationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LexikJWTAuthenticationBundle extends Bundle
{
    public function registerBundles()
    {
        return array(
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),
        );
    }
}