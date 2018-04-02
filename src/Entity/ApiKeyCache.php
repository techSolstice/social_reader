<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiKeyCacheRepository")
 */
class ApiKeyCache
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $api_name;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $api_key;

    /**
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    private $api_value;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_updated;

    public function getId()
    {
        return $this->id;
    }

    public function getApiName(): ?string
    {
        return $this->api_name;
    }

    public function setApiName(string $api_name): self
    {
        $this->api_name = $api_name;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->api_key;
    }

    public function setApiKey(string $api_key): self
    {
        $this->api_key = $api_key;

        return $this;
    }

    public function getApiValue(): ?string
    {
        return $this->api_value;
    }

    public function setApiValue(?string $api_value): self
    {
        $this->api_value = $api_value;

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->last_updated;
    }

    public function setLastUpdated(\DateTimeInterface $last_updated): self
    {
        $this->last_updated = $last_updated;

        return $this;
    }
}
