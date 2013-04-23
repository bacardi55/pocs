<?php
namespace Pocs\Entity;

class Frontend implements \JsonSerializable
{
    protected $id;
    protected $base_url;
    protected $name;
    protected $apikey;

    public function getId()
    {
        return $this->id;
    }
    public function getBaseUrl()
    {
        return $this->base_url;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getApiKey()
    {
        return $this->apikey;
    }

    public function importFromArray(Array $frontend)
    {
        $this->id = $frontend['id'];
        $this->base_url = $frontend['base_url'];
        $this->name = $frontend['name'];
        $this->apikey = $frontend['apikey'];
    }

    public function transformInArray()
    {
        return array(
            'id' => $this->id,
            'base_url' => $this->base_url,
            'name' => $this->name,
            'apikey' => $this->apikey,
        );
    }

    public function jsonSerialize()
    {
        return $this->transformInArray();
    }
}
