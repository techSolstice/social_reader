<?php
namespace App\Service;

use App\Entity\ApiKeyCache;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception;

class ApiToken
{
    public function retrieve_token($apiname, $apikey)
    {
        return '';
    }
}