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
     * @Route("/social/twitter/{search_query}", defaults={"search_query"="vegas"})
     * @param string $search_query
     * @param TwitterService $twitterService
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function twitter_index($search_query, TwitterService $twitterService)
    {
        // Validate user input is alphanumeric.  Otherwise, default search string before passing to service.
        if (ctype_alnum($search_query) !== true){$search_query = 'vegas';}
        $twitterService->set_endpoint_argument($search_query);

        return $this->render('social/twitter.html.twig',
            [
                'controller_name' => 'SocialController',
                'streams_array' => $twitterService->request_status_sample(),
                'search_string' => $search_query
            ]
        );
    }

}
