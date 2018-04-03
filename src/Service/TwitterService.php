<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TwitterService
{
    const TWITTER_API_NAME = 'twitter';
    const TWITTER_OAUTH_ENDPOINT = 'oauth2/token';
    const TWITTER_STATUS_SAMPLING_ENDPOINT = 'statuses/sample.json';

    protected $em;
    protected $container;
    protected $apiKeyCache;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->apiKeyCache = new ApiKey($em, $this->container);
    }

    public function retrieve_access_token()
    {
        $access_token = $this->apiKeyCache->retrieve_token(self::TWITTER_API_NAME, 'access_token');

        if (!$access_token)
        {
            $bearer_token = $this->assemble_bearer_token();
            $access_token = $this->generate_access_token($bearer_token);
            $this->cache_access_token($this->apiKeyCache,
                                      $this->get_consumer_key(),
                                      $this->get_consumer_secret(),
                                      $access_token
                                     );
        }

        return $access_token;
    }

    protected function get_consumer_key()
    {
        return $this->container->getParameter(self::TWITTER_API_NAME)['consumer_key'];
    }

    protected function get_consumer_secret()
    {
        return $this->container->getParameter(self::TWITTER_API_NAME)['consumer_secret'];
    }

    public function assemble_bearer_token() : string
    {
        $consumer_key = $this->get_consumer_key();
        $consumer_secret = $this->get_consumer_secret();

        return base64_encode($consumer_key . ':' . $consumer_secret);
    }

    public function generate_access_token($bearer_token) : string
    {
        $client = new Client();
        try {
            $response = $client->request(
                'POST',
                $this->container->getParameter('twitter')['oauth_host'] . self::TWITTER_OAUTH_ENDPOINT,
                ['headers' => array('Authorization' => 'Basic ' . $bearer_token,
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'),
                    'body' => 'grant_type=client_credentials'
                ]
            );

            $token_array = json_decode($response->getBody(), true);

            if ($token_array['token_type'] == 'bearer')
            {
                $access_token = $token_array['access_token'];
            }else{
                $access_token = '';
            }

        }catch (Exception\GuzzleException $guzzle_ex)
        {
            $access_token = '';
            echo $guzzle_ex->getMessage();

        }

        return $access_token;
    }

    public function cache_access_token(ApiKey $apiKey, $api_name, $api_key, $api_value)
    {
        $apiKey->store_access_token($api_name, $api_key, $api_value);
    }

    /*
     * Organize the below into a separate service and refactor this as an auth service
     */

    public function form_auth_string()
    {
        $auth_string =
        'OAuth oauth_consumer_key=”' . $this->get_consumer_key() .
        '”, oauth_nonce=”' . $this->get_consumer_secret() .
        '”, oauth_signature=”' . $oauth_sig .
        '”, oauth_signature_method=”HMAC-SHA1”, oauth_timestamp=”' .
        '”, oauth_token=”' . $this->generate_access_token($this->assemble_bearer_token()) . '",oauth_version=”1.0”';

        return $auth_string;
    }

    public function request_status_sample()
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $this->container->getParameter('twitter')['api_host'] . self::TWITTER_STATUS_SAMPLING_ENDPOINT,
                ['headers' => ['authorization' => $this->container->getParameter('twitch')['client_id']]]
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