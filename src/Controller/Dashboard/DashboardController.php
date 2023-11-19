<?php

namespace App\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class DashboardController extends AbstractController
{
    #[Route(path: '/admin', name: 'app_default')]
    public function index(Request $request): Response
    {
        
        return $this->render('dashboard/index.html.twig', [
           
        ]);
    }


    #[Route(path: '/iframe', name: 'app_dashboard_iframe', methods: ['GET', 'DELETE'], condition:"request.query.get('r')", options: ['expose' => true])]
    public function iframe(Request $request, UrlGeneratorInterface $urlGenerator)
    {
        $routeName = $request->query->get('r');
        $title = $request->query->get('title');
        $params = $request->query->all()['params'] ?? [];
        $iframeUrl = $urlGenerator->generate($routeName, $params);
       
        return $this->render('dashboard/iframe.html.twig', [
            'iframe_url' => $iframeUrl,
            'title' => $title,
            'facture' => false
        ]);
    }
}