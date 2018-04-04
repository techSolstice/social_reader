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
    protected $endpointArgument;

    public function __construct(EntityManagerInterface $em, ApiKey $apiKeyCache, $api_host, $oauth_host, $consumer_key, $consumer_secret)
    {
        $this->em = $em;
        $this->apiKeyCache = $apiKeyCache;
        $this->apiHost = $api_host;
        $this->oauthHost = $oauth_host;
        $this->consumerKey = $consumer_key;
        $this->consumerSecret = $consumer_secret;
    }

    public function set_endpoint_argument($endpoint_argument) : void
    {
        $this->endpointArgument = $endpoint_argument;
    }

    /**
     * Returns an app access token, whether it is an existing one or needs to be generated
     * @return string
     */
    public function retrieve_access_token() : string
    {
        // Retrieve an existing token
        $access_token = $this->apiKeyCache->retrieve_token(self::TWITTER_API_NAME, 'access_token');

        // If the token doesn't exist, generate one based on the bearer token
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

    /**
     * Assemble the app bearer token, which is just a concatenated consumer key and secret
     * @return string
     */
    private function assemble_bearer_token() : string
    {
        return base64_encode($this->consumerKey . ':' . $this->consumerSecret);
    }

    /**
     * Send a request for a new application access token. Returns empty string if unable to generate.
     * @param $bearer_token
     * @return string
     */
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

            //Load the response into an array then check of the token type is bearer
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
        }

        return $access_token;
    }

    /**
     * Store the passed in access token onto the database.
     * @param ApiKey $apiKey
     * @param $api_name     string Name of the API we're using
     * @param $api_key      string Name for the token we want to store
     * @param $api_value    string Value of the token we want to store.
     */
    private function cache_access_token(ApiKey $apiKey, $api_name, $api_key, $api_value)
    {
        $apiKey->store_access_token($api_name, $api_key, $api_value);
    }

    /**
     * Compose an array containing the name-value pairings needed for a Authentication header.  These parings may be needed to generate additional pairings, like a signature.
     * @return array
     */
    private function get_query_string_array() : array
    {
        return array(
                'oauth_consumer_key' => $this->consumerKey,
                'oauth_nonce' => $this->consumerSecret,
                'oauth_timestamp' => strval(time()),
                'oauth_token' => $this->retrieve_access_token(),
                'oauth_version' => '1.0'
        );
    }

    /**
     * Generate signature base key; used as the basis for the signature
     * @param $method           string GET | POST
     * @param $host             string Hostname for the endpoint
     * @param $endpoint         string REST endpoint
     * @param $parameterArray   array OAUTH Parameters
     * @return string
     */
    private function generate_sig_base_string($method, $host, $endpoint, $parameterArray)
    {
        return rawurlencode($method) . '&' . rawurlencode($host . $endpoint) . '&' . http_build_query($parameterArray, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Compose a signing key, based on the consumer secret and the access token (aka "token secret" too...)
     * @param $consumer_secret
     * @param $token_secret     string access token
     * @return string
     */
    private function get_signing_key($consumer_secret, $token_secret)
    {
        return rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);
    }

    /**
     * Generate the actual signature we'll be signing the authorization headers with.
     * @param $sig_base_string
     * @param $signing_key
     * @return string
     */
    private function generated_signature($sig_base_string, $signing_key)
    {
        return base64_encode(strtoupper(hash_hmac('sha1', $sig_base_string, $signing_key)));
    }

    /**
     * The Bearer access token header string needed for authentication
     * @return string
     */
    public function compose_app_oauth_string(): string
    {
        return 'Bearer ' . $this->retrieve_access_token();
    }

    /**
     * Compose the user oauth string to be used for authorization. Has access to different endpoints.
     * @return string
     */
    public function compose_user_oauth_string() : string
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

    /**
     * Searches tweets based on provided string
     * @return array
     */
    public function request_status_sample()
    {
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $this->apiHost . self::TWITTER_STATUS_SAMPLING_ENDPOINT,
                ['headers' => ['authorization' => $this->compose_app_oauth_string()],
                 'query' => ['q' => $this->endpointArgument, 'count' => '10']
                ]
            );

            $streams_array = json_decode($response->getBody(), true);

        }catch (Exception\GuzzleException $guzzle_ex)
        {
            $streams_array = array();
        }

        return $streams_array['statuses'];
    }


}