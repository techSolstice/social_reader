<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TwitchService
{
    private const TWITCH_ENDPOINT_STREAMS = 'streams';
    protected $api_host;
    protected $client_id;

    // Allow Doctrine and YAML parameters to be accessible in this object
    public function __construct($api_host, $client_id)
    {
        $this->api_host = $api_host;
        $this->client_id = $client_id;
    }

    public function request_twitch_streams($num_streams='20') : array
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $this->api_host . self::TWITCH_ENDPOINT_STREAMS,
                ['headers' => ['Client-ID' => $this->client_id]]
            );

            $streams_array = json_decode($response->getBody(), true)['data'];

        }catch (Exception\GuzzleException $guzzle_ex)
        {
            $streams_array = array();
        }
        #@todo assert that we have a reasonable number

        return $streams_array;
    }
}