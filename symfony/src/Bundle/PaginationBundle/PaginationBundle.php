<?php

namespace App\Bundle\PaginationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PaginationBundle extends Bundle
{
    public function registerBundles()
    {
        return array(
            new Knp\Bundle\PaginatorBundle\KnpPaginatorBundle(),
        );
    }
}