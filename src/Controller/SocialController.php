<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Service\TwitchService;
use App\Service\TwitterService;

class SocialController extends Controller
{

    /**
     * @Route("/social/twitch/")
     * @param TwitchService $twitchService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function twitch_index(TwitchService $twitchService)
    {
        $response = $twitchService->request_twitch_streams();
        return $this->render('social/twitch.html.twig',[
            'controller_name' => 'SocialController', 'streams_array' => $response,
        ]);
    }

    /**
     * @Route("/social/twitter/")
     * @param TwitterService $twitterService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function twitter_index(TwitterService $twitterService)
    {
        $access_token = $twitterService->retrieve_access_token();
        print_r($twitterService->request_status_sample());
        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController', 'access_token' => $access_token, 'oauth' => $twitterService->compose_oauth_string(), 'streams_array' => $twitterService->request_status_sample()
        ]);
    }

}
