<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SocialController extends Controller
{
    /**
     * @Route("/social", name="home")
     */
    public function index()
    {
        return $this->render('social/index.html.twig', [
            'controller_name' => 'SocialController',
        ]);
    }
}
