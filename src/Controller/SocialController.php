<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;

class SocialController extends Controller
{
    private const TWITCH_API_HOST = 'https://api.twitch.tv/helix/';
    private const TWITCH_ENDPOINT_STREAMS = 'streams';
    private const TWITCH_CLIENT_ID = '';  #@todo let's make a separate config (singleton/static?)

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
     * @Route("/social/{medium}"), requirements={"medium"="twitter|reddit|youtube|twitch"}
     */
    public function serve_medium($medium)
    {
        switch($medium)
        {
            case 'twitter':
                $medium_response = $this->twitter_index();
                break;
            case 'twitch':
                $medium_response = $this->twitch_index();
            #@todo Account for invalid values and 404 or find better practice for requirements whitelist
        }

        #@todo Is there a better way of returning a response that might not have been set?
        if (!isset($medium_response)){return $this->index();}
        return $medium_response;
    }

    private function twitter_index()
    {
        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController',
        ]);
    }

    private function twitch_index()
    {
        $response = $this->request_twitch_streams();
        return $this->render('social/twitch.html.twig',[
            'controller_name' => 'SocialController', 'json_response' => $response,
        ]);
    }

    private function request_twitch_streams($num_streams='20')
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                self::TWITCH_API_HOST . SELF::TWITCH_ENDPOINT_STREAMS,
                ['headers' => ['Client-ID' => self::TWITCH_CLIENT_ID]]
            );
        }catch (Exception\RequestException $guzzle_ex)
        {
            $response = $guzzle_ex->getResponse();
        }
        #@todo assert that we have a reasonable number

        return $response->getBody();
    }

}
