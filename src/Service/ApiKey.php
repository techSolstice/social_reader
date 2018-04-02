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

    public function store_access_token($api_name, $api_key, $api_value)
    {
            $entityManager = $this->em;

            $api_key_cache = new ApiKeyCache();
            $api_key_cache->setApiName(self::TWITTER_API_NAME);
            $api_key_cache->setApiKey('access_token');
            $api_key_cache->setApiValue($api_value);
            $api_key_cache->setLastUpdated(new \DateTime());

            $entityManager->persist($api_key_cache);
    }
}
