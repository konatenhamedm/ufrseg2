<?php

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

use Psr\Container\ContainerInterface;


class ServiceRouter
{


    private mixed $route;




    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, RouterInterface $router)
    {

        if ($requestStack->getCurrentRequest()) {
            $this->route = $requestStack->getCurrentRequest()->attributes->get('_route');

        }

    }

    public function getRoute()
    {
        return $this->route;
    }

    //    public function verifyanddispatch() {
    //
    //
    //
    //    }
}
