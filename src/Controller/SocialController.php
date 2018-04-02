<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use App\Service\ApiKey;
use App\Service\TwitchService;
use App\Service\TwitterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class SocialController extends Controller
{

    /**
     * @Route("/test")
     * @return \Symfony\Component\HttpFoundation\Response
     */


    /**
     * @Route("/social/{medium}"), requirements={"medium"="twitter|reddit|youtube|twitch"}
     */
    public function serve_medium($medium)
    {
        switch($medium)
        {
            case 'twitter':
                $medium_response = $this->twitter_index(new TwitterService($this->getDoctrine()->getManager(), $this->container),
                                                        new ApiKey($this->getDoctrine()->getManager(), $this->container));
                break;
            case 'twitch':
                $medium_response = $this->twitch_index(new TwitchService($this->container));
            #@todo Account for invalid values and 404 or find better practice for requirements whitelist
        }

        #@todo Is there a better way of returning a response that might not have been set?
        if (!isset($medium_response)){return $this->index();}
        return $medium_response;
    }

    private function twitch_index(TwitchService $twitchService)
    {
        $response = $twitchService->request_twitch_streams();
        return $this->render('social/twitch.html.twig',[
            'controller_name' => 'SocialController', 'streams_array' => $response,
        ]);
    }

    public function twitter_index(TwitterService $twitterService, ApiKey $apikey)
    {
        $access_token = $twitterService->retrieve_access_token($apikey, $twitterService);
        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController', 'access_token' => $access_token
        ]);
    }


}
