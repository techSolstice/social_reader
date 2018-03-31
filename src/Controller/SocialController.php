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


    /**
     * @Route("/social/{medium}")
     */
    public function serve_medium($medium)
    {
        switch($medium)
        {
            case 'twitter':
                $medium_response = $this->twitter_index();
                break;
            #@todo Account for invalid values and 404 or perhaps route back to main home
        }

        #@todo Is there a better way of returning a response that might not have been set?
        if (!isset($medium_response)){return $this->index();}
        return $medium_response;
    }

    public function twitter_index()
    {
        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController',
        ]);
    }

}
