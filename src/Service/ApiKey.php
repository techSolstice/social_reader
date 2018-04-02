<?php

namespace App\Service;

use App\Entity\ApiKeyCache;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiKey
{
    const TWITTER_API_NAME = 'twitter';
    const TWITTER_OAUTH_ENDPOINT = 'oauth2/token';

    // Allow Doctrine and YAML parameters to be accessible in this object
    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Query database for cached API token; generate a new one if not exists
     * @param $api_name
     * @param $api_key
     * @return null|object|string
     */
    public function retrieve_token($api_name, $api_key)
    {
        $token = '';

        $api_key = $this->em
                    ->getRepository(ApiKeyCache::class)
                    ->findOneBy(array(
                        'api_name' => $api_name,
                        'api_key' => $api_key
                    ));

        if (!$api_key)
        {
            //Let's generate a new key!
            if ($api_name = self::TWITTER_API_NAME && $api_key == 'access_token')
            {
                $token = $this->generate_twitter_access_token();
            }
        }else{
            $token = $api_key->getApiValue();
        }

        return $token;
    }

    private function generate_twitter_access_token()
    {
        $consumer_key = $this->container->getParameter(self::TWITTER_API_NAME)['consumer_key'];

        $consumer_secret = $this->container->getParameter(self::TWITTER_API_NAME)['consumer_secret'];

        $bearer_token = base64_encode($consumer_key . ':' . $consumer_secret);

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
            echo 'test';
            $token_array = json_decode($response->getBody(), true);
            var_dump($token_array);
            //['data'];

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

        //Commit a new access token iff empty
        if ($access_token != '')
        {
            $entityManager = $this->em;

            $api_key_cache = new ApiKeyCache();
            $api_key_cache->setApiName(self::TWITTER_API_NAME);
            $api_key_cache->setApiKey('access_token');
            $api_key_cache->setApiValue($access_token);
            $api_key_cache->setLastUpdated(new \DateTime());

            $entityManager->persist($api_key_cache);
            $entityManager->flush();
        }

        return $access_token;
    }
}
