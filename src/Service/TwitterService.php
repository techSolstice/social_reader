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
    const TWITTER_STATUS_SAMPLING_ENDPOINT = 'search/tweets.json';

    protected $em;
    protected $apiKeyCache;
    protected $apiHost;
    protected $oauthHost;
    protected $consumerKey;
    protected $consumerSecret;

    public function __construct(EntityManagerInterface $em, ApiKey $apiKeyCache, $api_host, $oauth_host, $consumer_key, $consumer_secret)
    {
        $this->em = $em;
        $this->apiKeyCache = $apiKeyCache;
        $this->apiHost = $api_host;
        $this->oauthHost = $oauth_host;
        $this->consumerKey = $consumer_key;
        $this->consumerSecret = $consumer_secret;
    }

    public function retrieve_access_token() : string
    {
        $access_token = $this->apiKeyCache->retrieve_token(self::TWITTER_API_NAME, 'access_token');

        if (!$access_token)
        {
            $bearer_token = $this->assemble_bearer_token();
            $access_token = $this->generate_access_token($bearer_token);
            $this->cache_access_token($this->apiKeyCache,
                                      $this->consumerKey,
                                      $this->consumerSecret,
                                      $access_token
                                     );
        }

        return $access_token;
    }

    public function assemble_bearer_token() : string
    {
        return base64_encode($this->consumerKey . ':' . $this->consumerSecret);
    }

    private function generate_access_token($bearer_token) : string
    {
        $client = new Client();
        try {
            $response = $client->request(
                'POST',
                $this->oauthHost . self::TWITTER_OAUTH_ENDPOINT,
                ['headers' => array('Authorization' => 'Basic ' . $bearer_token,
                                    'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
                                   ),
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

    public function get_query_string_array() : array
    {
        return array(
                'oauth_consumer_key' => $this->consumerKey,
                'oauth_nonce' => $this->consumerSecret,
                'oauth_timestamp' => strval(time()),
                'oauth_token' => $this->retrieve_access_token(),
                'oauth_version' => '1.0'
        );
    }

    private function generate_sig_base_string($method, $host, $endpoint, $parameterArray)
    {
        return rawurlencode($method) . '&' . rawurlencode($host . $endpoint) . '&' . http_build_query($parameterArray, '', '&', PHP_QUERY_RFC3986);
    }

    private function get_signing_key($consumer_secret, $token_secret)
    {
        return rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);
    }

    private function generated_signature($sig_base_string, $signing_key)
    {
        return base64_encode(strtoupper(hash_hmac('sha1', $sig_base_string, $signing_key)));
    }

    public function compose_oauth_string() : string
    {
        $oauth_string = 'OAuth ';
        $sig_base_string = $this->generate_sig_base_string('PUT', $this->apiHost, $this::TWITTER_STATUS_SAMPLING_ENDPOINT, $this->get_query_string_array());
        $oauth_array = $this->get_query_string_array();
        $oauth_array['oauth_signature'] = $this->generated_signature($sig_base_string, $this->get_signing_key($this->consumerSecret, $this->retrieve_access_token()));
        $oauth_array['oauth_signature_method'] = 'HMAC-SHA1';

        ksort($oauth_array);

        $counter = 0;
        foreach ($oauth_array as $key => $value)
        {
            if (++$counter != 1)
            {
                $oauth_string .= ', ';
            }

            $oauth_string .= rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return $oauth_string;
    }

    public function request_status_sample()
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $this->apiHost . self::TWITTER_STATUS_SAMPLING_ENDPOINT,
                ['headers' => ['authorization' => $this->compose_oauth_string()],
                 'query' => ['q' => 'vegas']
                ]
            );

            $streams_array = json_decode($response->getBody(), true)['data'];

        }catch (Exception\GuzzleException $guzzle_ex)
        {
            echo $guzzle_ex->getMessage();
            echo $guzzle_ex->getTraceAsString();
            $streams_array = array();
        }

        return $streams_array;
    }


}