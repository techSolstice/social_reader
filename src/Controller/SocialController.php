<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use App\Service\ApiKey;

class SocialController extends Controller
{
    private const TWITCH_ENDPOINT_STREAMS = 'streams';

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
     * @Route("/test")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function twitter_index(ApiKey $apikey)
    {
        $access_token = $apikey->retrieve_token($apikey::TWITTER_API_NAME, 'access_token');

        return $this->render('social/twitter.html.twig', [
            'controller_name' => 'SocialController', 'access_token' => $access_token
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


    private function twitch_index()
    {
        $response = $this->request_twitch_streams();
        return $this->render('social/twitch.html.twig',[
            'controller_name' => 'SocialController', 'streams_array' => $response,
        ]);
    }

    private function request_twitch_streams($num_streams='20')
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $this->getParameter('twitch')['api_host'] . self::TWITCH_ENDPOINT_STREAMS,
                ['headers' => ['Client-ID' => $this->getParameter('twitch')['client_id']]]
            );

            $streams_array = json_decode($response->getBody(), true)['data'];

        }catch (Exception\GuzzleException $guzzle_ex)
        {
            $streams_array = array();
        }
        #@todo assert that we have a reasonable number

        return $streams_array;
    }

    /*private function request_twitter_streams()
    {
    }*/

}
