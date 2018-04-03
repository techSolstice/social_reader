<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Service\ApiKey;
use App\Service\TwitchService;
use App\Service\TwitterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class SocialController extends Controller
{

    protected $twitterService;
    protected $twitchService;
    protected $apiKeyCache;

    /*public function __construct()
    {
        //$this->twitterService = new TwitterService($this->container, $this->apiKeyCache);

    }*/

    /**
     * @Route("/social/{medium}"), requirements={"medium"="twitter|twitch"}
     */
    public function serve_medium($medium)
    {
        //$this->twitchService = new TwitchService($this->container);
        $this->twitterService = new TwitterService($this->container);

        switch($medium)
        {
            case 'twitter':
                $medium_response = $this->twitter_index();
                break;
            case 'twitch':
                $medium_response = $this->twitch_index($this->twitchService);
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

    public function twitter_index()
    {
        $access_token = $this->twitterService->retrieve_access_token();
        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController', 'access_token' => $access_token
        ]);
    }


}
